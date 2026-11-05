<?php

namespace App\Exceptions;

use RuntimeException;

class TokenStorageUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Sanctum token storage is not configured.');
    }
}
