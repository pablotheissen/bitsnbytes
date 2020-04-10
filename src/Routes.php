<?php

declare(strict_types=1);


return [
    [
        'GET',
        '/',
        ['Bitsbytes\Controllers\EntryController', 'showLatest'],
        'home',
    ],
    [
        'GET',
        '/entry/new',
        ['Bitsbytes\Controllers\EntryController', 'newform'],
        'new-entry',
    ],
    [
        'POST',
        '/entry/new',
        ['Bitsbytes\Controllers\EntryController', 'saveEntry'],
    ],
    [
        'GET',
        '/entry/[:slug]',
        ['Bitsbytes\Controllers\EntryController', 'showBySlug'],
    ],
    [
        'GET',
        '/entry/[:slug]/edit',
        ['Bitsbytes\Controllers\EntryController', 'editformBySlug'],
        'edit-entry',
    ],
    [
        'POST',
        '/entry/[:slug]/edit',
        ['Bitsbytes\Controllers\EntryController', 'saveEntry'],
    ],
];
