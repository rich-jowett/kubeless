<?php

namespace Kubeless\Exception;

interface HttpExceptionInterface
{
    public function getHttpStatusCode(): int;
    public function getHttpStatusMessage(): string;
}
