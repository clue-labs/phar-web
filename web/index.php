<?php

use Clue\PharWeb\PackageManager;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

Request::setTrustedProxies(array('127.0.0.1'));

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
    $pm = $app['package_manager'];

    $package = $pm->getPackage($vendor . '/' . $name);
    $stabilities = $pm->getStability()->getVersionsPerStability($package);

    return $app['twig']->render('package.twig', array(
        'package'     => $package,
        'stabilities' => $stabilities,
        'vendor'      => $vendor,
        'name'        => $name,
        'filename'    => $name . '.phar'
    ));
})->bind('package');

$app->get($prefix . '/about', function() {
    return 'about';
})->bind('about');

$app->get($prefix . '/stats', function() {
    return 'stats';
})->bind('stats');

$app->get($prefix . '/api', function() {
    return 'api';
})->bind('api');

$app->run();
