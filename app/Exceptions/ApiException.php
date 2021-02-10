<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $code;

    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message);
        $this->code = $code;
    }

    public function getHttpCode()
    {
        return $this->code;
    }
}
