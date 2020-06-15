<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\User;


use Bitsnbytes\Models\RecordNotFoundException;

class UserNotFoundException extends RecordNotFoundException
{
    /**
     * @var string
     */
    public $message = 'The user you requested does not exist.';
}