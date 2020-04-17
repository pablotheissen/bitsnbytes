<?php

declare(strict_types=1);

use Auryn\Injector;


$injector = new Injector();
//$injector->share('Auryn\Injector');

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

$injector->share('Erusev\Parsedown');
// TODO: add parsedown settings

$injector->alias('Bitsnbytes\Models\Template\Renderer', 'Bitsnbytes\Models\Template\MustacheRenderer');
$injector->define(
    'Mustache_Engine',
    [
        ':options' => [
            'loader' => new Mustache_Loader_FilesystemLoader(
                __DIR__ . '/../templates', ['extension' => '.html']
            ),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(
                __DIR__ . '/../templates/partials',
                ['extension' => '.html']
            ),
            'helpers' => [
                'format_date' => [
                    'atom' => function ($value): ?string {
                        if ($value instanceof DateTimeInterface) {
                            return $value->format(DateTimeInterface::ATOM);
                        }
                        return null;
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
//                'markdown' => function($value) {
//
//                },
            ],
        ]
    ]
);

$injector->share('Bitsnbytes\Models\Entry\EntryRepository');
$injector->share('Bitsnbytes\Models\TagRepository');

$injector->share('PDO');
$injector->define(
    'PDO',
    [
        ':dsn' => $config['database_dsn'],
    ]
);
$pdo = $injector->make('PDO');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$injector->share('AltoRouter');

return $injector;