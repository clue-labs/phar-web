<?php

namespace Clue\PharWeb;

use Packagist\Api\Result\Package;
use Packagist\Api\Client as PackagistClient;
use BadMethodCallException;
use InvalidArgumentException;

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

        return 'download ' . $tag;
    }
}
