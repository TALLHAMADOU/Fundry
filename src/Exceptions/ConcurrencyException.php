<?php

namespace Hamadou\Fundry\Exceptions;

use RuntimeException;

class ConcurrencyException extends RuntimeException
{
    protected $code = 0;

    public function __construct($message = 'Concurrency error', $code = 0)
    {
        parent::__construct($message, $code);
    }
}
