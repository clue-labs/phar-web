<?php

namespace Clue\PharWeb\Job;

class Build
{
    public function perform()
    {
        $package = $this->args['package'];
        $version = $this->args['version'];
        $outfile = $this->args['outfile'];

        chdir('/home/me/workspace/phar-composer/');
        exec('php -d phar.readonly=off bin/phar-composer build ' . escapeshellarg($package . ':' . $version) . ' ' . escapeshellarg($outfile) . ' 2>&1', $lines, $code);

        if ($code !== 0) {
            throw new \UnexpectedValueException('Build error');
        }
    }
}
