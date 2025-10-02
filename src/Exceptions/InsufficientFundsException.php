<?php

namespace Hamadou\Fundry\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    protected $code = 0;

    public function __construct($message = 'Insufficient funds', $code = 0)
    {
        parent::__construct($message, $code);
    }
}
