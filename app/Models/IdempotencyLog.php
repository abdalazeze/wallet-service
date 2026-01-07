<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyLog extends Model
{
    public $timestamps = false; // Using only created_at

    protected $fillable = [
        'idempotency_key',
        'request_hash',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];
}
