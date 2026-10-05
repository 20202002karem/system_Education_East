<?php

namespace App\Exceptions;

use RuntimeException;

/** Raised by M2 services; the controller maps it to the M1 error envelope. */
class DomainConflictException extends RuntimeException
{
    public function __construct(public string $errorCode, string $message, public int $status = 409, public array $fields = [])
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return \App\Support\ApiResponse::error($this->errorCode, $this->getMessage(), $this->status, $this->fields);
    }
}
