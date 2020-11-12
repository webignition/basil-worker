<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle\Handler;

class HostStatusCodeGenerator
{
    private RequestCountStatusCodeMap $statusCodeMap;
    private int $requestIndex = 0;

    public function __construct(string $hostname)
    {
        $this->statusCodeMap = new RequestCountStatusCodeMap($hostname);
    }

    public function getNext(): int
    {
        $statusCode = $this->statusCodeMap->getStatusCodeForRequestIndex($this->requestIndex);

        $this->requestIndex++;

        return $statusCode;
    }
}
