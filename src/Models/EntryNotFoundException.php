<?php

declare(strict_types=1);


namespace Bitsbytes\Models;


class EntryNotFoundException extends RecordNotFoundException
{
    /**
     * @var string
     */
    public $message = 'The entry you requested does not exist.';
}