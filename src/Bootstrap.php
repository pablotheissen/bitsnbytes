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

$environment = 'development';

// Create Container using PHP-DI
$container_builder = new ContainerBuilder();
$container_builder->addDefinitions(
    [
        'dsn' => 'sqlite:' . __DIR__ . '/../data/bitsnbytes.use.sqlite',
    ]
);
// $containerBuilder->addDefinitions('config.php');

$container = $container_builder->build();
$container->set(
    PDO::class,
    new \PDO($container->get('dsn'))
);
$container->set(
    App::class,
    function (ContainerInterface $container) {
        AppFactory::setContainer($container);
        return AppFactory::create();
    }
);
$container->set(
    Twig::class,
    function () {
        return Twig::create(__DIR__ . '/templates'/*, ['cache' => 'path/to/cache']*/);
    }
);
// For the responder
$container->set(
    ResponseFactoryInterface::class,
    function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    }
);
// The Slim RouterParser
$container->set(
    RouteParserInterface::class,
    function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    }
);

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

//require_once 'Application/Helper.php';
//
//error_reporting(E_ALL);
//
///** @var array<mixed> $config */
//$config = include __DIR__ . '/../config/config.php';
//date_default_timezone_set($config['timezone']);
//