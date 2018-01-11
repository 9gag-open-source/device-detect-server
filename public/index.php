<?php

require __DIR__.'/../vendor/autoload.php';

// Create and configure Slim app
use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\OperatingSystem;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

$config = [
    'settings' => [
        'displayErrorDetails' => true,
        'routerCacheFile' => sys_get_temp_dir() . '/route-cache',
    ],
    'notFoundHandler' => function ($c) {
        return function () use ($c) {
            return $c['response']
                ->withStatus(404)
                ->withJson([]);
        };
    },
    'errorHandler' => function ($c) {
        return function ($req, $resp, Exception $ex) use ($c) {
            return $c['response']
                ->withStatus(500)
                ->withJson(['error' => [
                    'message' => $ex->getMessage(),
                    'details' => $ex->__toString(),
                ]]);
        };
    },
];
$cacheMaxAge = getenv('CACHE_CONTROL_MAX_AGE') ?: 86400;

$app = new App($config);

// Set up Device-Detect
$dd = new DeviceDetector();
$ddCache = 'file';
if (extension_loaded('apcu')) {
    $ddCache = 'apcu';
    $dd->setCache(new PSR6Bridge(new Symfony\Component\Cache\Adapter\ApcuAdapter()));
} else {
    $dd->setCache(new PSR6Bridge(new Symfony\Component\Cache\Adapter\FilesystemAdapter()));
}

// Define app routes
$app->get('/v1/detect', function (Request $req,  Response $resp, $args = []) use ($dd) {
    $userAgent = $req->getQueryParam('ua', '');
    $dd->setUserAgent($userAgent);
    $dd->parse();

    return $resp->withJson([
        'client' => $dd->getClient(), // holds information about browser, feed reader, media player, ...
        'browser_family' => Browser::getBrowserFamily($dd->getClient('short_name')) ?: 'Unknown',
        'os' => $dd->getOs(),
        'os_family' => OperatingSystem::getOsFamily($dd->getOs('short_name')) ?: 'Unknown',
        'device' => [
            'type' => $dd->getDeviceName(),
            'brand' => $dd->getBrand(),
            'model' => $dd->getModel(),
        ],
        'bot' => $dd->getBot(),
    ]);
})->add(new \Slim\HttpCache\Cache('public', $cacheMaxAge));

$app->get('/v1/health-check', function (Request $req,  Response $resp) use ($ddCache) {
   return $resp->withJson([
       'status' => 'OK',
       'dd-cache' => $ddCache,
       'php-version' => phpversion(),
   ]);
});

// Run app
$app->run();