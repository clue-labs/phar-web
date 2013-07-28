<?php

namespace Clue\PharWeb\Model;

use Clue\PharWeb\PackageManager;
use Packagist\Api\Result\Package\Version as PackagistVersion;

class Version
{
    private $manager;
    private $packagist;

    public function __construct(PackageManager $manager, PackagistVersion $packagist)
    {
        $this->manager = $manager;
        $this->packagist = $packagist;
    }

    public function getId()
    {
        return $this->packagist->getVersion();
    }

    public function getDate()
    {
        return $this->packagist->getTime();
    }

    public function getBin()
    {
        return $this->packagist->getBin();
    }
}
