<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use App\HttpMessage\CallbackRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class CallbackSender
{
    private HttpClientInterface $httpClient;
    private JobStore $jobStore;
    private CallbackResponseHandler $callbackResponseHandler;
    private CallbackStateMutator $callbackStateMutator;
    private int $retryLimit;

    public function __construct(
        HttpClientInterface $httpClient,
        JobStore $jobStore,
        CallbackResponseHandler $callbackResponseHandler,
        CallbackStateMutator $callbackStateMutator,
        int $retryLimit
    ) {
        $this->httpClient = $httpClient;
        $this->jobStore = $jobStore;
        $this->callbackResponseHandler = $callbackResponseHandler;
        $this->callbackStateMutator = $callbackStateMutator;
        $this->retryLimit = $retryLimit;
    }

    public function send(CallbackInterface $callback): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        if ($callback->hasReachedRetryLimit($this->retryLimit)) {
            $this->callbackStateMutator->setFailed($callback);

            return;
        }

        $job = $this->jobStore->getJob();
        $request = new CallbackRequest($callback, $job);

        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 300) {
                $this->callbackResponseHandler->handleResponse($callback, $response);
            }
        } catch (ClientExceptionInterface $httpClientException) {
            $this->callbackResponseHandler->handleClientException($callback, $httpClientException);
        }
    }
}
