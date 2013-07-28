<?php

namespace Clue\PharWeb\Model;

use Clue\PharWeb\PackageManager;

use Clue\PharWeb\Stability;

use Packagist\Api\Result\Package as PackagistPackage;

class Package
{
    public static function load(PackageManager $manager, $packagename)
    {
        $packagist = $manager->getClient()->get($packagename);

        return new Package($manager, $packagist);
    }

    private $manager;
    private $packagist;

    protected function __construct(PackageManager $manager, PackagistPackage $packagist)
    {
        $this->manager = $manager;
        $this->packagist = $packagist;
    }

    public function getName()
    {
        return $this->packagist->getName();
    }

    public function getNameSub()
    {
        $name = $this->getName();
        $pos = strpos($name, '/');

        return substr($name, $pos + 1);
    }

    public function getDescription()
    {
        return $this->packagist->getDescription();
    }

    public function getVendor()
    {
        return new Vendor($this->getVendorName());
    }

    public function getNameOfVendor()
    {
        $name = $this->getName();
        $pos = strpos($name, '/');

        return substr($name, 0, $pos);
    }

    public function getVersions()
    {
        $versions = array();

        foreach ($this->packagist->getVersions() as $version) {
            $versions[$version->getVersion()] = new Version($this->manager, $version);
        }

        return $versions;
    }

    public function getVersionsPerStability()
    {
        return $this->manager->getStability()->getVersionsPerStability($this->getVersions());
    }

    public function getVersionInfo($versionString)
    {
        foreach ($this->getVersions() as $version) {
            if ($version->getVersion() === $versionString) {
                return $version;
            }
        }
        throw new InvalidArgumentException('Error, the requested version does not exist!');
    }

    public function getVersionDefault()
    {
        return $this->manager->getStability()->getVersionStability($this->getVersions(), 'stable');
    }

    public function getStability()
    {
        return $this->manager->getStability()->getStabilityMax($this->getVersions());
    }

    public function getFilename()
    {

    }
}
