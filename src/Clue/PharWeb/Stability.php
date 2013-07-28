<?php

namespace Clue\PharWeb;

use Packagist\Api\Result\Package;
use Composer\Package\Version\VersionParser;
use UnderflowException;

class Stability
{
    public function getVersionsPerStability($allversions)
    {
        $levels = array();

        foreach ($this->getStabilities() as $level) {
            $versions = $this->getVersionsStability($allversions, $level);
            if ($versions) {
                $levels[$level] = $versions;
            }
        }

        return $levels;
    }

    public function getVersionsStability($versions, $stability)
    {
        $level = $this->getStabilityLevel($stability);
        $ret = array();

        foreach ($versions as $version) {
            if ($this->getStabilityLevel(VersionParser::parseStability($version->getVersion())) == $level) {
                $ret []= $version;
            }
        }

        return $ret;
    }

    public function getVersionStability($versions, $stability)
    {
        foreach ($this->getVersionsStability($versions, $stability) as $version) {
            return $version;
        }
        throw new UnderflowException('Error, unable to find default version');
    }

    public function getStabilities()
    {
        $l = array(
            'stable' => 4,
            'RC'     => 3,
            'beta'   => 2,
            'alpha'  => 1,
            'dev'    => 0
        );
        return array_keys($l);
    }

    public function getStabilityLevel($stability)
    {
        $l = array(
            'stable' => 4,
            'RC'     => 3,
            'beta'   => 2,
            'alpha'  => 1,
            'dev'    => 0
        );
        return $l[$stability];
    }
}
