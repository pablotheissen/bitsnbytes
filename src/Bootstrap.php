<?php

declare(strict_types=1);

namespace Bitsnbytes;

use Bitsnbytes\Helpers\Configuration;
use Bitsnbytes\Helpers\SessionManager;
use Bitsnbytes\Helpers\SessionMiddleware;
use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Psr7\Response;
use Slim\Views\TwigMiddleware;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

require __DIR__ . '/../vendor/autoload.php';

// General helper functions that are completely unrelated to Bitsnbytes
require_once 'Application/helper.php';

// Create Container using PHP-DI
$container_builder = new ContainerBuilder();

// Application settings
$config = new Configuration(require __DIR__ . '/../config/config.php');
$container_builder->addDefinitions([Configuration::class => $config]);

$container_builder->addDefinitions($config->get('root') . '/src/Application/container.php');
if ($config->get('environment') === 'development') {
    error_reporting(E_ALL);

    // Use whoops for displaying fatal errors
    $shutdown_handler = function (): void {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->handleShutdown();
    };
    register_shutdown_function($shutdown_handler);
} else {
    error_reporting(0);
    $container_builder->enableCompilation($config->get('container_cache'));
}
$container = $container_builder->build();

// Set container to create App with on AppFactory
$app = $container->get(App::class);

// Basic settings
$config = $container->get(Configuration::class);
date_default_timezone_set($config->timezone);

// Middleware
// Set Content Length Header
$contentLengthMiddleware = new ContentLengthMiddleware();
$app->add($contentLengthMiddleware);

// Strip trailing slashes from URI
$app->add(
    function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if ($path !== '/' && substr($path, -1) === '/') {
            // recursively remove slashes when its more than 1 slash
            $path = rtrim($path, '/');

            // permanently redirect paths with a trailing slash
            // to their non-trailing counterpart
            $uri = $uri->withPath($path);

            if ($request->getMethod() === 'GET') {
                /** @var ResponseInterface $response */
                $response = new Response();
                return $response
                    ->withHeader('Location', (string)$uri)
                    ->withStatus(301);
            } else {
                $request = $request->withUri($uri);
            }
        }

        return $handler->handle($request);
    }
);

// Twig-View Middleware
$app->add(TwigMiddleware::class);

// Session Middleware
$sessionManager = new SessionManager($config);
$sessionMiddleware = new SessionMiddleware($sessionManager);
$app->add($sessionMiddleware);

// Register routes
$routes = require __DIR__ . '/Application/routes.php';
$routes($app);
// Activate route caching
if ($config->get('environment') !== 'development') {
    $routeCollector = $app->getRouteCollector();
    $routeCollector->setCacheFile($config->router_cache);
}
$app->addRoutingMiddleware();

// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app, $config) : ResponseInterface {
    $response = $app->getResponseFactory()->createResponse();
    // Todo: add logging: $logger->error($exception->getMessage());
    if ($config->get('environment') === 'development') {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $html = $whoops->handleException($exception);
        $response->getBody()->write($html);
    } else {
        $response->getBody()->write('');
    }
    return $response;
};

$error_middleware = $app->addErrorMiddleware(true, true, true);
$error_middleware->setDefaultErrorHandler($customErrorHandler);

$app->run();