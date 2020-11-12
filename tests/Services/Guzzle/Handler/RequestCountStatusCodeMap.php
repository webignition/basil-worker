<?php

declare(strict_types=1);

namespace App\Tests\Services\Guzzle\Handler;

class RequestCountStatusCodeMap
{
    /**
     * @var int[]
     */
    private array $statusCodes = [];

    public function __construct(string $hostname)
    {
        $hostnameParts = explode('.', $hostname);
        $filteredHostnameParts = array_filter($hostnameParts, function (string $part) {
            return (int) $part > 0;
        });

        foreach ($filteredHostnameParts as $filteredHostnamePart) {
            $this->statusCodes[] = (int) $filteredHostnamePart;
        }
    }

    public function getStatusCodeForRequestIndex(int $requestIndex): int
    {
        while (false === array_key_exists($requestIndex, $this->statusCodes) && $requestIndex > -1) {
            $requestIndex--;
        }

        return $requestIndex >= 0 ? $this->statusCodes[$requestIndex] : 0;
    }
}
