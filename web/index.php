<?php

use Clue\PharWeb\PackageManager;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['package_manager'] = new PackageManager();

// TODO: remove me
$app['debug'] = true;
$prefix = '/phar'; // '';
//$app['routes']->addPrefix('/phar/');

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig');
})->bind('homepage');

$app->get($prefix . '/{vendor}/', function($vendor) use ($app) {
    $packages = $app['package_manager']->getNamesOfPackagesForVendor($vendor);

    return $app['twig']->render('vendor.twig', array(
        'packages' => $packages,
        'vendor' => $vendor
    ));
})->bind('vendor');

$app->get($prefix . '/{vendor}/{name}.phar', function ($vendor, $name, Request $request) use ($app) {
    $package = $app['package_manager']->getPackage($vendor . '/' . $name);
    $version = $request->get('version', null);

    return $app['package_manager']->requestDownload($package, $version);
})->bind('download');

$app->get($prefix . '/{vendor}/{name}', function ($vendor, $name) use ($app) {
    $package = $app['package_manager']->getPackage($vendor . '/' . $name);

    return $app['twig']->render('package.twig', array(
        'package'  => $package,
        'vendor'   => $vendor,
        'name'     => $name,
        'filename' => $name . '.phar'
    ));
})->bind('package');

$app->run();
