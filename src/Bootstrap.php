<?php

declare(strict_types=1);

namespace Bitsnbytes;

use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Views\TwigMiddleware;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require __DIR__ . '/../vendor/autoload.php';

define('ENVIRONMENT', 'development'); // development | production

if(ENVIRONMENT === 'development'){
    error_reporting(E_ALL);

    // Use whoops for displaying fatal errors
    $shutdown_handler = function (): void {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->handleShutdown();
    };
    register_shutdown_function($shutdown_handler);
}

// Create Container using PHP-DI
$container_builder = new ContainerBuilder();
$container_builder->addDefinitions(
    [
        'dsn' => 'sqlite:' . __DIR__ . '/../data/bitsnbytes.use.sqlite',
    ]
);
$container_builder->addDefinitions(require 'Application/container.php');

$container = $container_builder->build();

// Set container to create App with on AppFactory
$app = $container->get(App::class);

// Middleware
$contentLengthMiddleware = new ContentLengthMiddleware();
$app->add($contentLengthMiddleware);

// Add Twig-View Middleware
//https://github.com/odan/slim4-skeleton/blob/7d0ed7c1ad77c54c34eef027e8c62d81380d88a8/config/container.php#L68
//$app->add(TwigMiddleware::createFromContainer($app));
$app->add(TwigMiddleware::class);


// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app) : ResponseInterface {
    // Todo: add logging: $logger->error($exception->getMessage());
    if(ENVIRONMENT === 'development'){
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $html = $whoops->handleException($exception);
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write($html);
    }

    return $response;
};

$error_middleware = $app->addErrorMiddleware(true, false, false);
$error_middleware->setDefaultErrorHandler($customErrorHandler);

// Define custom shutdown handler
$shutdown_handler = function (): void {
    $whoops = new Run();
    $whoops->pushHandler(new PrettyPageHandler());
    $whoops->handleShutdown();
};
register_shutdown_function($shutdown_handler);


// Register routes
$routes = require __DIR__ . '/Application/Routes.php';
$routes($app);

$app->run();

//require_once 'Application/Helper.php';
//
//error_reporting(E_ALL);
//
///** @var array<mixed> $config */
//$config = include __DIR__ . '/../config/config.php';
//date_default_timezone_set($config['timezone']);
//