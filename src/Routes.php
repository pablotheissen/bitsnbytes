<?php

declare(strict_types=1);


return [
    [
        'GET',
        '/',
        ['Bitsnbytes\Controllers\EntryController', 'showLatest'],
        'home',
    ],
    [
        'GET',
        '/entry/new',
        ['Bitsnbytes\Controllers\EntryController', 'newform'],
        'new-entry',
    ],
    [
        'POST',
        '/entry/new',
        ['Bitsnbytes\Controllers\EntryController', 'saveEntry'],
    ],
    [
        'GET',
        '/entry/[:slug]',
        ['Bitsnbytes\Controllers\EntryController', 'showBySlug'],
    ],
    [
        'GET',
        '/entry/[:slug]/edit',
        ['Bitsnbytes\Controllers\EntryController', 'editformBySlug'],
        'edit-entry',
    ],
    [
        'POST',
        '/entry/[:slug]/edit',
        ['Bitsnbytes\Controllers\EntryController', 'saveEntry'],
    ],
];
