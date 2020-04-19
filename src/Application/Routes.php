<?php

declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
    $app->get('/', \Bitsnbytes\Controllers\EntryController::class . ':showLatest');
    $app->group(
        '/entry',
        function (Group $group) {
            $group->get('/new', \Bitsnbytes\Controllers\EntryController::class . ':newform')
                ->setName('new-entry');
            $group->post('/new', \Bitsnbytes\Controllers\EntryController::class . ':saveEntry');
            $group->get('/{slug}', \Bitsnbytes\Controllers\EntryController::class . ':showBySlug');
            $group->get('/{slug}/edit', \Bitsnbytes\Controllers\EntryController::class . ':editformBySlug')
                ->setName('edit-entry');
            $group->post('/{slug}/edit', \Bitsnbytes\Controllers\EntryController::class . ':saveEntry');
        }
    );
    $app->get('/tag/{tag}', \Bitsnbytes\Controllers\EntryController::class . ':showByTag');
};