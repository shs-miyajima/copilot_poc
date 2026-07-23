<?php

namespace App\Exceptions;

use Exception;

class DuplicateResponseException extends Exception
{
    public function __construct(string $message = '既にこのアンケートに回答済みです。')
    {
        parent::__construct($message);
    }
}
