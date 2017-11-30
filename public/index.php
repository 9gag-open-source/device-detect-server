<?php

require __DIR__.'/../vendor/autoload.php';

// Create and configure Slim app
use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
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
        'os' => $dd->getOs(),
        'device' => $dd->getDevice(),
        'brand' => $dd->getBrandName(),
        'model' => $dd->getModel(),
        'bot' => $dd->getBot(),
    ]);
})->add(new \Slim\HttpCache\Cache('public', 86400));

$app->get('/v1/health-check', function (Request $req,  Response $resp) use ($ddCache) {
   return $resp->withJson([
       'status' => 'OK',
       'dd-cache' => $ddCache,
       'php-version' => phpversion(),
   ]);
});

// Run app
$app->run();