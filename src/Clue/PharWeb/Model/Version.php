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

    public function getBuild()
    {
        return new Build($this->getIdOfBuild(), $this->manager);
    }

    public function getIdOfBuild()
    {
        return $this->manager->getRedis()->GET('package::' . $this->getNameOfPackage() . '::' . $this->getId() . '::build');
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

    public function getStatusOfBuild()
    {
        return $this->getBuild()->getStatus();
    }

    public function getStatusOfJob()
    {
        $sob = new Resque_Job_Status($this->getIdOfJob());
        $status = $sob->get();

        return $status;
    }

    public function doEnsureHasBuild()
    {
        $bid = $this->getIdOfBuild();

        if ($bid === null) {
            $redis = $this->manager->getRedis();

            // TODO: lock for very short duration

            $bid = $this->getIdOfBuild();

            // check if build is still unknown (to avoid race condition)
            if ($bid === null) {
                $bid = $redis->INCR('id::build');

                $jid = Resque::enqueue('build', 'Clue\\PharWeb\\Job\\Build', array(
                    'package' => $this->getNameOfPackage(),
                    'version' => $this->getId(),
                    'outfile' => $this->getOutfile(),
                    'build'   => $bid
                ), true);

                $redis->MULTI();
                $redis->SET('package::' . $this->getNameOfPackage() . '::' . $this->getId() . '::build', $bid);
                $redis->SADD('package::' . $this->getNameOfPackage() . '::builds', $bid);

                // store list of last 100 build IDs
                $redis->lpush('build::last::started', $bid);
                $redis->ltrim('build::last::started', 0, 99);

                $redis->SET('build::' . $bid . '::package', $this->getNameOfPackage());
                $redis->SET('build::' . $bid . '::version', $this->getId());
                $redis->SET('build::' . $bid . '::job', $jid);
                $redis->SET('build::' . $bid . '::status', Build::STATUS_PENDING);
                $redis->SET('build::' . $bid . '::dateStarted', date(DATE_ISO8601));
                $redis->EXEC();
            }

            // TODO: unlock
        }
    }
}
