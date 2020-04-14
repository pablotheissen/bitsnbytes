<?php

declare(strict_types=1);


namespace Bitsbytes\Models\Tag;


use Bitsbytes\Models\RecordNotFoundException;

class TagNotFoundException extends RecordNotFoundException
{
    /**
     * @var string
     */
    public $message = 'The tag you requested does not exist.';
}