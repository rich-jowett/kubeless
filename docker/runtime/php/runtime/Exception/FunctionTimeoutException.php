<?php

namespace Kubeless\Exception;

use Throwable;

class FunctionTimeoutException extends HttpException
{
    public function __construct(string $message = "Request Timeout", int $code = 408, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
