<?php

namespace App\Exceptions\Wallet;

use Exception;

class DuplicateIdempotencyKeyException extends Exception
{
    protected $message = 'Duplicate request detected';
    protected $code = 409;

    public function __construct(public readonly array $cachedResponse)
    {
        parent::__construct($this->message, $this->code);
    }
}
