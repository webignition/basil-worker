<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\CallbackHttpExceptionEvent;
use App\Event\CallbackHttpResponseEvent;
use App\HttpMessage\CallbackRequest;
use App\Model\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CallbackSender
{
    private HttpClientInterface $httpClient;
    private JobStore $jobStore;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        HttpClientInterface $httpClient,
        JobStore $jobStore,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->httpClient = $httpClient;
        $this->jobStore = $jobStore;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function send(CallbackInterface $callback): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        $job = $this->jobStore->getJob();
        $request = new CallbackRequest($callback, $job);

        try {
            $response = $this->httpClient->sendRequest($request);

            if (200 !== $response->getStatusCode()) {
                $this->eventDispatcher->dispatch(
                    new CallbackHttpResponseEvent($callback, $response),
                    CallbackHttpResponseEvent::NAME
                );
            }
        } catch (ClientExceptionInterface $httpClientException) {
            $this->eventDispatcher->dispatch(
                new CallbackHttpExceptionEvent($callback, $httpClientException),
                CallbackHttpExceptionEvent::NAME
            );

            return;
        }
    }
}
