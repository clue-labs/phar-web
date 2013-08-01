<?php

namespace Clue\PharWeb\Model;

use Clue\PharWeb\PackageManager;
use Clue\PharWeb\Stability;
use Packagist\Api\Result\Package as PackagistPackage;
use UnexpectedValueException;

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

    /**
     *
     * @param string $versionString
     * @throws InvalidArgumentException
     * @return Version
     */
    public function getVersion($versionString)
    {
        foreach ($this->getVersions() as $version) {
            if ($version->getId() === $versionString) {
                return $version;
            }
        }
        throw new InvalidArgumentException('Error, the requested version does not exist!');
    }

    public function getVersionDefault()
    {
        return $this->getVersionStability('stable');
    }

    public function getVersionStability($stability)
    {
        return $this->manager->getStability()->getVersionStability($this->getVersions(), $stability);
    }

    public function getStability()
    {
        return $this->manager->getStability()->getStabilityMax($this->getVersions());
    }

    public function getFilename()
    {
        return $this->getNameSub() . '.phar';
    }


    /**
     *
     * @param int $buildId
     * @throws UnexpectedValueException
     * @return Build
     */
    public function getBuild($buildId)
    {
        $build = Build::load($buildId, $this->manager);

        if ($build->getNameOfPackage() !== $this->getName()) {
            throw new UnexpectedValueException('Invalid package');
        }

        return $build;
    }

    /**
     *
     * @return Build[]
     */
    public function getBuilds()
    {
        $builds = array();

        foreach ($this->manager->getRedis()->SMEMBERS('package::' . $this->getName() . '::builds') as $bid) {
            $builds[$bid] = new Build($bid, $this->manager);
        }

        return $builds;
    }
}
