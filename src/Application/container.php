<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

return [
    // Application settings
//    Configuration::class => function () {
//        return new Configuration(require __DIR__ . '/settings.php');
//    },
    App::class => function (ContainerInterface $container): App {
        AppFactory::setContainer($container);
        return AppFactory::create();
    },
    PDO::class => function (ContainerInterface $container): PDO {
        return new PDO($container->get('dsn'));
    },
    Twig::class => function (): Twig {
        return Twig::create(__DIR__ . '/../templates'/*, ['cache' => 'path/to/cache']*/);
    },
    // For the responder
    ResponseFactoryInterface::class => function (ContainerInterface $container): ResponseFactoryInterface {
        return $container->get(App::class)->getResponseFactory();
    },
    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container): RouteParserInterface {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },
];