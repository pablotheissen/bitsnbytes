<?php

declare(strict_types=1);

use Auryn\Injector;


$injector = new Injector();
$injector->share('Auryn\Injector');

//$injector->share('CONFIG');
//$injector->define('CONFIG',
//    include(__DIR__ . '/../config/config.php'));

$injector->defineParam('config', $config);

$injector->alias('Http\Request', 'Http\HttpRequest');
$injector->share('Http\HttpRequest');
$injector->define(
    'Http\HttpRequest',
    [
        ':get' => $_GET,
        ':post' => $_POST,
        ':cookies' => $_COOKIE,
        ':files' => $_FILES,
        ':server' => $_SERVER,
    ]
);

$injector->alias('Http\Response', 'Http\HttpResponse');
$injector->share('Http\HttpResponse');

$injector->alias('Bitsbytes\Template\Renderer', 'Bitsbytes\Template\MustacheRenderer');
$injector->define(
    'Mustache_Engine',
    [
        ':options' => [
            'loader' => new Mustache_Loader_FilesystemLoader(
                __DIR__ . '/templates', ['extension' => '.html']
            ),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(
                __DIR__ . '/templates/partials',
                ['extension' => '.html']
            ),
            'helpers' => [
                'format_date' => [
                    'atom' => function ($value) {
                        return $value->format(DateTimeInterface::ATOM);
                    },
                    'atom_date' => function ($value): ?string {
                        if ($value instanceof DateTimeInterface) {
                            return $value->format('Y-m-d');
                        }
                        return null;
                    },
                    'atom_time' => function ($value): ?string {
                        if ($value instanceof DateTimeInterface) {
                            return $value->format('H:i:s');
                        }
                        return null;
                    },
                    'short' => function ($value) use ($config): ?string {
                        if ($value instanceof DateTimeInterface) {
                            return $value->format($config['date_formats']['short']);
                        }
                        return null;
                    },
                ],
                'case' => [
                    'lower' => function ($value) {
                        return strtolower((string)$value);
                    },
                    'upper' => function ($value) {
                        return strtoupper((string)$value);
                    },
                ],
            ],
        ]
    ]
);

$injector->share('Bitsbytes\Models\EntryRepository');

$injector->share('PDO');
$injector->define(
    'PDO',
    [
        ':dsn' => $config['database_dsn'],
    ]
);

$injector->share('AltoRouter');

return $injector;