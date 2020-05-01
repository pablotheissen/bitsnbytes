<?php
declare(strict_types=1);

$settings = [];

$settings['root'] = __DIR__ . '/..';
$settings['temp'] = $settings['root'] . '/tmp';

$settings['environment'] = 'production'; // development | production

$settings['date_formats'] = [
    'short' => 'd.m.Y',
];
$settings['timezone'] = 'Europe/Berlin';

$settings['db'] = [
    'dsn' => 'sqlite:' . $settings['root'] . '/data/bitsnbytes.sqlite',
];

$settings['router_cache'] = $settings['temp'] . '/router/router_cache.php';

$settings['container_cache'] = $settings['temp'] . '/container';

$settings['basepath'] = ''; // without trailing slash, '' for no basepath

$settings['twig'] = [
    'paths' => [
        __DIR__ . '/../src/templates',
    ],
    'options' => [
        'cache_enabled' => true, // Should be set to true in production
        'cache_path' => $settings['temp'] . '/twig',
    ],
];

return $settings;