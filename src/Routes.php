<?php

declare(strict_types=1);


return [
    [
        'GET',
        '/',
        ['Bitsbytes\Controllers\EntryController', 'showLatest'],
        'home'
    ],
    [
        'GET',
        '/entry/[a:slug]',
        ['Bitsbytes\Controllers\EntryController', 'showBySlug']
    ],
    [
        'GET',
        '/entry/[a:slug]/edit',
        ['Bitsbytes\Controllers\EntryController', 'editformBySlug'],
        'edit-entry'
    ],
    [
        'POST',
        '/entry/[a:slug]/edit',
        ['Bitsbytes\Controllers\EntryController', 'saveEntry']
    ],
];
