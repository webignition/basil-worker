<?php

declare(strict_types=1);

namespace App\HttpMessage;

use App\Entity\Job;
use App\Model\Callback\CallbackInterface;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class CallbackRequest extends GuzzleRequest
{
    private const METHOD = 'POST';

    public function __construct(CallbackInterface $callback, Job $job)
    {
        parent::__construct(
            self::METHOD,
            $job->getCallbackUrl(),
            [
                'content-type' => 'application/json',
            ],
            json_encode([
                'label' => $job->getLabel(),
                'type' => $callback->getType(),
                'payload' => $callback->getData(),
            ])
        );
    }
}
