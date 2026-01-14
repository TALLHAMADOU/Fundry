<?php

namespace Hamadou\Fundry\Exceptions;

use RuntimeException;

class UnauthorizedWalletException extends RuntimeException
{
    protected $code = 403;

    public function __construct(string $message = 'Ce portefeuille ne vous appartient pas', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
