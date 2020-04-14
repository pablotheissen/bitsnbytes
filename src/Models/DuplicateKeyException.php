<?php

declare(strict_types=1);


namespace Bitsbytes\Models;


use Exception;

class DuplicateKeyException extends Exception
{
    /**
     * @var string
     */
    public $message = 'Tried to create a record with an already existing id/slug.';
}