<?php

declare(strict_types=1);

namespace Bitsbytes;

use _HumbugBox09702017065e\Nette\DI\Extensions\InjectExtension;
use AltoRouter;
use Auryn\Injector;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require __DIR__ . '/../vendor/autoload.php';

require_once 'Helper.php';

error_reporting(E_ALL);

$environment = 'development';

/**
 * Register the error handler
 */
$whoops = new Run();
if ($environment !== 'production') {
    $whoops->pushHandler(new PrettyPageHandler);
} else {
    $whoops->pushHandler(
        function ($e) {
            echo 'Todo: Friendly error page and send an email to the developer';
        }
    );
}
$whoops->register();

$config = include __DIR__ . '/../config/config.php';
$injector = include 'Dependencies.php';
date_default_timezone_set($config['timezone']);

$request = $injector->make('Http\HttpRequest');
$response = $injector->make('Http\HttpResponse');

$router = $injector->make('AltoRouter');
$router->setBasePath($config['basepath']);

$routes = include('Routes.php');
foreach ($routes as $route) {
    if (!isset($route[3])) {
        $route[3] = null;
    }
    $router->map($route[0], $route[1], $route[2], $route[3]);
}

$match = $router->match();

// call closure or throw 404 status
if (is_array($match)) {
    $className = $match['target'][0];
    $method = $match['target'][1];

    $class = $injector->make($className);
    $class->$method($match['params']);
} else {
    // no route was matched
    $response->setContent('404 - Page not found');
    $response->setStatusCode(404);
}

foreach ($response->getHeaders() as $header) {
    header($header, false);
}

echo $response->getContent();
