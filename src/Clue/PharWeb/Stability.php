<?php

namespace Clue\PharWeb;

use Packagist\Api\Result\Package;
use Composer\Package\Version\VersionParser;

class Stability
{
    public function getVersionsStability(Package $package, $stability)
    {
        $level = $this->getStabilityLevel($stability);
        $ret = array();

        foreach ($package->getVersions() as $version) {
            $v = $version->getVersion();
            if ($this->getStabilityLevel(VersionParser::parseStability($v)) >= $level) {
                $ret []= $v;
            }
        }

        return $ret;
    }

    public function getVersionStability(Package $package, $stability)
    {
        foreach ($this->getVersionsStability($package, $stability) as $version) {
            return $version;
        }
        throw new BadMethodCallException('Error, unable to find default version');
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
