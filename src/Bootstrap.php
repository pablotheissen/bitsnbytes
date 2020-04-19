<?php

declare(strict_types=1);

namespace Bitsnbytes;

use DI\ContainerBuilder;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Throwable;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Middleware
$contentLengthMiddleware = new ContentLengthMiddleware();
$app->add($contentLengthMiddleware);


// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app) {
    // Todo: add logging: $logger->error($exception->getMessage());
    // Todo: differentiate between dev/prod instance
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
    $html = $whoops->handleException($exception);
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write($html);

    return $response;
};

$error_middleware = $app->addErrorMiddleware(true, false, false);
$error_middleware->setDefaultErrorHandler($customErrorHandler);

// Define custom shutdown handler
$shutdown_handler = function () {
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
    $whoops->handleShutdown();
};
register_shutdown_function($shutdown_handler);


// Register routes
$routes = require __DIR__ . '/Application/Routes.php';
$routes($app);

$app->run();



//use AltoRouter;
//use Auryn\Injector;
//use Http\HttpRequest;
//use Http\HttpResponse;
//use Whoops\Handler\PrettyPageHandler;
//use Whoops\Run;
//
//require __DIR__ . '/../vendor/autoload.php';
//
//require_once 'Application/Helper.php';
//
//error_reporting(E_ALL);
//
//$environment = 'development';
//
///**
// * Register the error handler
// */
//$whoops = new Run();
//if ($environment !== 'production') {
//    $whoops->pushHandler(new PrettyPageHandler());
//} else {
//    $whoops->pushHandler(
//        function ($e): void {
//            // TODO: create better error handling for production
//            echo 'Todo: Friendly error page and send an email to the developer';
//        }
//    );
//}
//$whoops->register();
//
///** @var array<mixed> $config */
//$config = include __DIR__ . '/../config/config.php';
//date_default_timezone_set($config['timezone']);
//
///** @var Injector $injector */
//$injector = include('Application/Dependencies.php');
//
///** @var HttpRequest $request */
//$request = $injector->make('Http\Request');
//
///** @var HttpResponse $response */
//$response = $injector->make('Http\Response');
//
///** @var AltoRouter $router */
//$router = $injector->make('AltoRouter');
//$router->setBasePath($config['basepath']);
//
///** @var array<array<mixed>> $routes */
//$routes = include('Application/Routes.php');
//foreach ($routes as $route) {
//    if (!isset($route[3])) {
//        $route[3] = null;
//    }
//    $router->map($route[0], $route[1], $route[2], $route[3]);
//}
//
//$match = $router->match();
//
//// call closure or throw 404 status
//if (is_array($match)) {
//    $className = $match['target'][0];
//    $method = $match['target'][1];
//
//    $class = $injector->make($className);
//    $class->$method($match['params']);
//} else {
//    // no route was matched
//    $response->setContent('404 - Page not found');
//    $response->setStatusCode(404);
//}
//
//foreach ($response->getHeaders() as $header) {
//    header($header, false);
//}
//
//echo $response->getContent();
