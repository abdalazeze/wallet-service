<?php

namespace App\Exceptions\Wallet;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $message = 'Insufficient balance for this operation';
    protected $code = 422;
}
