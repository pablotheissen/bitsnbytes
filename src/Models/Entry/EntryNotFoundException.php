<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\Entry;


use Bitsnbytes\Models\RecordNotFoundException;

class EntryNotFoundException extends RecordNotFoundException
{
    /**
     * @var string
     */
    public $message = 'The entry you requested does not exist.';
}