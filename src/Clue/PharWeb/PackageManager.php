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

    public function getRedis()
    {
        return Resque::redis();
    }

    public function requestDownload(Package $package, $versionIdentifier = null)
    {
        if ($versionIdentifier === null) {
            $versionIdentifier = $package->getVersionDefault()->getId();
            return new RedirectResponse('?version=' . $versionIdentifier, 302);
        }

        $version = $package->getVersion($versionIdentifier);

        $version->doEnsureHasJob();

        $jid = $version->getIdOfJob();
        $status = $version->getStatus();

        if ($status === false) {
            throw new UnexpectedValueException('Found job ID "' . $jid . '", but could not track its status');
        }

        if ($status === Resque_Job_Status::STATUS_FAILED) {
            throw new UnexpectedValueException('Job with ID "' . $jid . '" failed');
        }

        if ($status !== Resque_Job_Status::STATUS_COMPLETE) {
            $waiting = 1;
            sleep(1);
            return new RedirectResponse('?version=' . $versionIdentifier . '&waiting=' . $waiting, 302);
        }

        $outfile = $version->getOutfile();

        // if complete:
        return new StreamedResponse(function() use ($outfile) {
            readfile($outfile);
        }, 201, array(
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => filesize($outfile)
        ));
    }
}
