<?php
declare(strict_types=1);

return [
    'date_formats' => [
        'short' => 'd.m.Y'
    ],
    'db' => [
        'dsn' => 'sqlite:' . __DIR__ . '/../data/bitsnbytes.sqlite'
    ],
    'timezone' => 'Europe/Berlin',
    'basepath' => '', // without trailing slash, '' for no basepath
    'twig' => [
        'paths' => [
            __DIR__ . '/../src/templates',
        ],
        'options' => [
            // Should be set to true in production
            'cache_enabled' => false,
//            'cache_path' => $settings['temp'] . '/twig',
        ],
    ],
];