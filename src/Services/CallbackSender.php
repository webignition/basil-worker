<?php

declare(strict_types=1);

namespace App\Services;

use App\HttpMessage\CallbackRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

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
        if (false === $this->jobStore->has()) {
            return;
        }

        if ($callback->hasReachedRetryLimit($this->retryLimit)) {
            $this->callbackStateMutator->setFailed($callback);

            return;
        }

        $job = $this->jobStore->get();
        $request = new CallbackRequest($callback, $job);

        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 300) {
                $this->callbackResponseHandler->handle($callback, $response);
            } else {
                $this->callbackStateMutator->setComplete($callback);
            }
        } catch (ClientExceptionInterface $httpClientException) {
            $this->callbackResponseHandler->handle($callback, $httpClientException);
        }
    }
}
