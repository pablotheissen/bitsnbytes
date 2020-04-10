<?php
declare(strict_types=1);

return [
    'date_formats' => [
        'short' => 'd.m.Y'
    ],
    'database_dsn' => 'sqlite:' . __DIR__ . '/../data/bitsnbytes.sqlite',
    'timezone' => 'Europe/Berlin',
    'basepath' => '', // without trailing slash, '' for no basepath
];
