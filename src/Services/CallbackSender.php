<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Callback\CallbackInterface;
use App\HttpMessage\CallbackRequest;
use App\Services\EntityStore\JobStore;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class CallbackSender
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private JobStore $jobStore,
        private CallbackResponseHandler $callbackResponseHandler,
        private CallbackStateMutator $callbackStateMutator,
    ) {
    }

    public function send(CallbackInterface $callback): void
    {
        if (false === $this->jobStore->has()) {
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
