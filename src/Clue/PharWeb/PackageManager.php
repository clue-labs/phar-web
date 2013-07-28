<?php

namespace Clue\PharWeb;

use Clue\PharWeb\Model\Vendor;
use Clue\PharWeb\Model\Package;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Packagist\Api\Client as PackagistClient;
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use Resque;
use Resque_Job_Status;

class PackageManager
{
    private $client;
    private $stability;

    public function __construct()
    {
        $this->client = new PackagistClient();
        $this->stability = new Stability();
    }

    public function getStability()
    {
        return $this->stability;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getVendor($vendorname)
    {
        return Vendor::load($this, $vendorname);
    }

    public function getPackage($packagename)
    {
        return Package::load($this, $packagename);
    }

    public function requestDownload(Package $package, $version = null)
    {
        if ($version === null) {
            $version = $package->getVersionDefault()->getVersion();
            return new RedirectResponse('?version=' . $version, 302);
        }

        $versionInfo = $package->getVersionInfo($version);
        $timestamp = strtotime($versionInfo->getTime());

        $tag = $package->getName() . ':' . $version . '@' . $timestamp;
        $outfile = sys_get_temp_dir() . '/' . md5($tag) . '.phar';

        $redis = Resque::redis();
        $jid = $redis->GET($tag);
        if ($jid === null) {
            // TODO: lock for very short duration

            $jid = $redis->GET($tag);

            // check if job is still unknown (so avoid this race condition)
            if ($jid === null) {
                $jid = Resque::enqueue('build', 'Clue\\PharWeb\\Job\\Build', array(
                    'package' => $package->getName(),
                    'version' => $version,
                    'outfile' => $outfile
                ), true);

                $redis->SET($tag, $jid);
                // TODO: unlock
            }
        }

        $sob = new Resque_Job_Status($jid);
        $status = $sob->get();

        if ($status === false) {
            throw new UnexpectedValueException('Found job ID "' . $jid . '", but could not track its status');
        }

        if ($status === Resque_Job_Status::STATUS_FAILED) {
            throw new UnexpectedValueException('Job with ID "' . $jid . '" failed');
        }

        if ($status !== Resque_Job_Status::STATUS_COMPLETE) {
            $waiting = 1;
            sleep(1);
            return new RedirectResponse('?version=' . $version . '&waiting=' . $waiting, 302);
        }

        // if complete:
        return new StreamedResponse(function() use ($outfile) {
            readfile($outfile);
        }, 201, array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => filesize($outfile)
        ));
    }
}
