<?php

declare(strict_types=1);

return [
    [
        'GET',
        '/',
        ['Bitsbytes\Controllers\EntryController', 'showLatest']
    ],
    [
        'GET',
        '/entry/{slug}',
        ['Bitsbytes\Controllers\EntryController', 'showBySlug']
    ],
    [
        'GET',
        '/entry/{slug}/edit',
        ['Bitsbytes\Controllers\EntryController', 'editformBySlug']
    ],
    [
        'POST',
        '/entry/{slug}/edit',
        ['Bitsbytes\Controllers\EntryController', 'saveEntry']
    ],
];
