<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Callback\CallbackInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class IntegrationCallbackRequestFactory
{
    public function __construct(
        private IntegrationJobProperties $jobProperties,
    ) {
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed>              $payload
     */
    public function create(string $type, array $payload): RequestInterface
    {
        return new Request(
            'POST',
            $this->jobProperties->getCallbackUrl(),
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode([
                'label' => $this->jobProperties->getLabel(),
                'type' => $type,
                'payload' => $payload,
            ])
        );
    }
}
