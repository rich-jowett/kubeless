<?php

namespace Kubeless\Exception;

abstract class HttpException extends \RuntimeException implements HttpExceptionInterface
{
    /**
     * HTTP Status Code
     *
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->code;
    }

    /**
     * HTTP Status Message
     *
     * @return string
     */
    public function getHttpStatusMessage(): string
    {
        return sprintf(
            "%d: %s",
            $this->code,
            $this->message
        );
    }
}
