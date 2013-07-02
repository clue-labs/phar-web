<?php

namespace Clue\PharWeb;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;

use Packagist\Api\Result\Package;
use Packagist\Api\Client as PackagistClient;
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use Resque;
use Resque_Job_Status;

class PackageManager
{
    public function __construct()
    {
        $this->client = new PackagistClient();
    }

    public function getNamesOfPackagesForVendor($vendor)
    {
        $packages = $this->client->all(array('vendor' => $vendor));

        if (!$packages) {
            throw new InvalidArgumentException('Invalid vendor name');
        }

        return $packages;
    }

    public function getPackage($packagename)
    {
        return $this->client->get($packagename);
    }

    public function getVersionInfo(Package $package, $versionString)
    {
        foreach ($package->getVersions() as $version) {
            if ($version->getVersion() === $versionString) {
                return $version;
            }
        }
        throw new InvalidArgumentException('Error, the requested version does not exist!');
    }

    public function getVersionDefault($package)
    {
        throw new BadMethodCallException('Error, unable to find default version');
    }

    public function requestDownload(Package $package, $version = null)
    {
        if ($version === null) {
            $version = $this->getVersionDefault($package);
        }

        $versionInfo = $this->getVersionInfo($package, $version);
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
