<?php

namespace Clue\PharWeb\Model;

use Clue\PharWeb\PackageManager;
use Packagist\Api\Result\Package\Version as PackagistVersion;
use Resque;
use Resque_Job_Status;

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

    public function getTimestamp()
    {
        return strtotime($this->getDate());
    }

    public function getBin()
    {
        return $this->packagist->getBin();
    }

    public function getPackage()
    {
        return $this->manager->getPackage($this->packagist->getName());
    }

    public function getNameOfPackage()
    {
        return $this->packagist->getName();
    }

    public function getIdOfJob()
    {
        $redis = $this->manager->getRedis();
        $tag   = $this->getTag();
        $jid   = $redis->GET($tag);

        return $jid;
    }

    public function getTag()
    {
        return $this->getNameOfPackage() . ':' . $this->getId() . '@' . $this->getTimestamp();
    }

    public function getOutfile()
    {
        return sys_get_temp_dir() . '/' . md5($this->getTag()) . '.phar';
    }

    public function getStatus()
    {
        $sob = new Resque_Job_Status($this->getIdOfJob());
        $status = $sob->get();

        return $status;
    }

    public function doEnsureHasJob()
    {
        $jid = $this->getIdOfJob();

        if ($jid === null) {
            $redis = $this->manager->getRedis();
            $tag   = $this->getTag();

            // TODO: lock for very short duration

            $jid = $redis->GET($tag);

            // check if job is still unknown (so avoid this race condition)
            if ($jid === null) {
                $jid = Resque::enqueue('build', 'Clue\\PharWeb\\Job\\Build', array(
                    'package' => $this->getNameOfPackage(),
                    'version' => $this->getId(),
                    'outfile' => $this->getOutfile(),
                ), true);

                $redis->SET($tag, $jid);
                // TODO: unlock
            }
        }

        return $this;
    }
}
