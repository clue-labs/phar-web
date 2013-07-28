<?php

namespace Clue\PharWeb\Model;

use Clue\PharWeb\PackageManager;
use Packagist\Api\Result\Package as PackagistPackage;
use OutOfBoundsException;

class Vendor
{
    public static function load(PackageManager $manager, $name)
    {
        $vendor = new Vendor($manager, $name);

        if (!$vendor->getNamesOfPackages()) {
            throw new OutOfBoundsException('Given vendor name has no packages listed on packagist');
        }

        return $vendor;
    }

    private $manager;
    private $name;
    private $packages = null;

    public function __construct(PackageManager $manager, $name)
    {
        $this->manager = $manager;
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPackages()
    {
        $packages = array();
        foreach ($this->getNamesOfPackages() as $packagename) {
            $packages[$packagename] = $this->manager->getPackage($packagename);
        }
        return $packages;
    }

    public function getPackage($packagename)
    {
        return $this->manager->getPackage($this->name . '/' . $packagename);
    }

    public function getNumberOfPackages()
    {
        return count($this->getNamesOfPackages());
    }

    public function getNamesOfPackages()
    {
        if ($this->packages === null) {
            $this->packages = $this->manager->getClient()->all(array('vendor' => $this->name));
        }

        return $this->packages;
    }
}
