<?php

namespace App\Exceptions\Wallet;

use Exception;

class CurrencyMismatchException extends Exception
{
    protected $message = 'Transfer only allowed between wallets with the same currency';
    protected $code = 422;
}
