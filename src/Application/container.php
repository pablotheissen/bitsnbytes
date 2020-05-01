<?php

declare(strict_types=1);

use Bitsnbytes\Helpers\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

return [
    App::class => function (ContainerInterface $container): App {
        AppFactory::setContainer($container);
        return AppFactory::create();
    },
    PDO::class => function (Configuration $config): PDO {
        return new PDO($config->get('db.dsn'));
    },
    Twig::class => function (Configuration $config): Twig {
        $options = $config->get('twig.options');
        $options['cache'] = $options['cache_enabled'] ? $options['cache_path'] : false;

        return Twig::create($config->get('twig.paths'), $options);
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