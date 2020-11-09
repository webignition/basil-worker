<?php

declare(strict_types=1);

namespace App\Services;

use App\Message\SendCallback;
use App\Model\Callback\CallbackInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CallbackResponseHandler
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function handleResponse(CallbackInterface $callback, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 300) {
            $callback->incrementRetryCount();

            $this->messageBus->dispatch(new SendCallback($callback));
        }
    }

    public function handleClientException(CallbackInterface $callback): void
    {
        $callback->incrementRetryCount();

        $this->messageBus->dispatch(new SendCallback($callback));
    }
}
