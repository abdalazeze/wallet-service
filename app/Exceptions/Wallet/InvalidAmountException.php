<?php

namespace App\Exceptions\Wallet;

use Exception;

class InvalidAmountException extends Exception
{
    protected $message = 'Amount must be a positive integer';
    protected $code = 422;
}
