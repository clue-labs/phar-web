<?php

namespace Clue\PharWeb\Job;

use Clue\PharWeb\PackageManager;
use Clue\PharWeb\Model\Build as BuildModel;
use Resque;
use Symfony\Component\Process\Process;
use UnexpectedValueException;

class Build
{
    public function perform()
    {
        $package = $this->args['package'];
        $version = $this->args['version'];
        $outfile = $this->args['outfile'];
        $bid     = $this->args['build'];

        $manager = new PackageManager();
        $build = $manager->getBuild($bid);

        if ($build->getStatus() !== BuildModel::STATUS_PENDING) {
            throw new UnexpectedValueException('Build in invalid state "' . $build->getStatus() . '", expected "' . BuildModel::STATUS_PENDING . '"');
        }
        $build->setStatus(BuildModel::STATUS_PROCESSING);

        $ok = true;

        $process = new Process('php -d phar.readonly=off bin/phar-composer build ' . escapeshellarg($package . ':' . $version) . ' ' . escapeshellarg($outfile), '/home/me/workspace/phar-composer/');
        $process->start();
        $code = $process->wait(function($type, $data) use ($build, &$ok) {
            $build->addLog($data);

            if ($type !== Process::OUT) {
                $ok = false;
            }
        });

        if (!$ok) {
            throw new UnexpectedValueException('Error running composer');
        }

        //$build->setLog(implode(PHP_EOL, $lines));

        if ($code !== 0) {
            $build->setStatus(BuildModel::STATUS_ERROR);
            throw new UnexpectedValueException('Build error');
        }

        $build->setStatus(BuildModel::STATUS_OK);
    }
}
