<?php

declare(strict_types=1);

namespace App\HttpMessage;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;

class CallbackRequest extends GuzzleRequest
{
    private const METHOD = 'POST';

    public function __construct(CallbackInterface $callback, Job $job)
    {
        parent::__construct(
            self::METHOD,
            (string) $job->getCallbackUrl(),
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode([
                'label' => $job->getLabel(),
                'type' => $callback->getType(),
                'payload' => $callback->getPayload(),
            ])
        );
    }
}
