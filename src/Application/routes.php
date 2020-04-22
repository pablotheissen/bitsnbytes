<?php

declare(strict_types=1);

use Bitsnbytes\Controllers\EntryController;
use Bitsnbytes\Controllers\ErrorController;
use Bitsnbytes\Controllers\RemoteController;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
    $app->get('/', EntryController::class . ':showLatest');
    $app->group(
        '/entry',
        function (Group $group): void {
            $group->get('/new', EntryController::class . ':newform')
                ->setName('new-entry');
            $group->post('/new', EntryController::class . ':saveEntry');
            $group->get('/{slug}', EntryController::class . ':showBySlug');
            $group->get('/{slug}/edit', EntryController::class . ':editformBySlug')
                ->setName('edit-entry');
            $group->post('/{slug}/edit', EntryController::class . ':saveEntry');
        }
    );
    $app->get('/tag/{tag}', EntryController::class . ':showByTag');
    $app->get('/fetch', RemoteController::class . ':fetchTitleAndDescription');

    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last
     */
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', ErrorController::class . ':error404');
};