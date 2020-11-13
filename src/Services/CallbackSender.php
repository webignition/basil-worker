<?php

declare(strict_types=1);

namespace App\Services;

use App\HttpMessage\CallbackRequest;
use App\Model\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class CallbackSender
{
    private HttpClientInterface $httpClient;
    private JobStore $jobStore;
    private CallbackResponseHandler $callbackResponseHandler;
    private int $retryLimit;

    public function __construct(
        HttpClientInterface $httpClient,
        JobStore $jobStore,
        CallbackResponseHandler $callbackResponseHandler,
        int $retryLimit
    ) {
        $this->httpClient = $httpClient;
        $this->jobStore = $jobStore;
        $this->callbackResponseHandler = $callbackResponseHandler;
        $this->retryLimit = $retryLimit;
    }

    public function send(CallbackInterface $callback): void
    {
        if (false === $this->jobStore->hasJob()) {
            return;
        }

        if ($callback->hasReachedRetryLimit($this->retryLimit)) {
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
