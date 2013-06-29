<?php

require __DIR__ . '/../vendor/autoload.php';

$worker = new Resque_Worker('build');
$worker->logLevel = Resque_Worker::LOG_NORMAL;
$worker->work(2);
