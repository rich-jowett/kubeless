<?php

namespace Kubeless\Exception;

use Throwable;

class InternalErrorException extends HttpException
{
    public function __construct(string $message = "Internal Server Error", int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
