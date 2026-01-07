<?php

namespace App\Exceptions\Wallet;

use Exception;

class SelfTransferException extends Exception
{
    protected $message = 'Cannot transfer to the same wallet';
    protected $code = 422;
}
