<?php

declare(strict_types=1);


namespace Bitsnbytes\Models\Tag;


use Bitsnbytes\Models\RecordNotFoundException;

class TagNotFoundException extends RecordNotFoundException
{
    /**
     * @var string
     */
    public $message = 'The tag you requested does not exist.';
}