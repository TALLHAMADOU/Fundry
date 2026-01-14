<?php

namespace Hamadou\Fundry\Exceptions;

use InvalidArgumentException;

class InvalidCurrencyException extends InvalidArgumentException
{
    protected $code = 400;

    public function __construct(string $message = 'Devise invalide', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
