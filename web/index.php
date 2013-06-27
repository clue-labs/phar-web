<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// TODO: remove me
$app['debug'] = true;
$prefix = '/phar'; // '';
//$app['routes']->addPrefix('/phar/');


$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig');
})->bind('homepage');

$app->get($prefix . '/{vendor}/', function($vendor) use ($app) {
    $client = new Packagist\Api\Client();
    $packages = $client->all(array('vendor' => $vendor));

    return $app['twig']->render('vendor.twig', array(
        'packages' => $packages,
        'vendor' => $vendor
    ));
})->bind('vendor');

$app->get($prefix . '/{vendor}/{name}.phar', function ($vendor, $name) use ($app) {
    return 'download ' . $vendor . ' / ' . $name;

    $client = new Packagist\Api\Client();
    $package = $client->get($vendor . '/' . $name);

    return $app['twig']->render('package.twig', array(
        'package' => $package,
        'vendor' => $vendor,
        'filename' => $name . '.phar'
    ));
})->bind('download');

$app->get($prefix . '/{vendor}/{name}', function ($vendor, $name) use ($app) {
    $client = new Packagist\Api\Client();
    $package = $client->get($vendor . '/' . $name);

    return $app['twig']->render('package.twig', array(
        'package'  => $package,
        'vendor'   => $vendor,
        'name'     => $name,
        'filename' => $name . '.phar'
    ));
})->bind('package');

$app->run();
