<?php

namespace Clue\PharWeb\Model;

use Clue\PharWeb\PackageManager;
use DateTime;

class Build
{
    const STATUS_NONE = 0;
    const STATUS_PENDING = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_OK = 3;
    const STATUS_ERROR = 4;

    public static function load($buildId, PackageManager $manager)
    {
        return new Build($buildId, $manager);
    }

    private $manager;
    private $id;

    public function __construct($id, PackageManager $manager)
    {
        $this->id = $id;
        $this->manager = $manager;
    }
    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return (int)$this->manager->getRedis()->GET('build::' . $this->id . '::status');
    }

    public function getNameOfPackage()
    {
        return $this->manager->getRedis()->GET('build::' . $this->id . '::package');
    }

    /**
     * @return Package
     */
    public function getPackage()
    {
        return $this->manager->getPackage($this->getNameOfPackage());
    }

    public function getIdOfVersion()
    {
        return $this->manager->getRedis()->GET('build::' . $this->id . '::version');
    }

    /**
     * @return Version
     */
    public function getVersion()
    {
        return $this->getPackage()->getVersion($this->getIdOfVersion());
    }

    public function getStatusText()
    {
        $l = array(
            self::STATUS_NONE => 'none',
            self::STATUS_PENDING => 'pending',
            self::STATUS_PROCESSING => 'processing',
            self::STATUS_OK => 'ok',
            self::STATUS_ERROR => 'error'
        );
        return $l[$this->getStatus()];
    }

    public function isFinished()
    {
        $status = $this->getStatus();
        return ($status === self::STATUS_OK || $status === self::STATUS_ERROR);
    }

    public function getDateStarted()
    {
        return new DateTime($this->manager->getRedis()->GET('build::' . $this->id . '::dateStarted'));
    }

    public function getLog()
    {
        return $this->manager->getRedis()->GET('build::' . $this->id . '::log');
    }

    public function setStatus($status)
    {
        $this->manager->getRedis()->SET('build::' . $this->id . '::status', $status);
    }

    public function setLog($log)
    {
        $this->manager->getRedis()->SET('build::' . $this->id . '::log', $log);
    }

    public function addLog($part)
    {
        $this->manager->getRedis()->APPEND('build::' . $this->id . '::log', $part);
    }
}