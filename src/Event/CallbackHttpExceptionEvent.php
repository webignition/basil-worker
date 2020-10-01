<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CallbackHttpExceptionEvent extends Event
{
    public const NAME = 'worker.callback.http-exception';

    private CallbackInterface $callback;
    private ClientExceptionInterface $exception;

    public function __construct(CallbackInterface $callback, ClientExceptionInterface $exception)
    {
        $this->callback = $callback;
        $this->exception = $exception;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }

    public function getException(): ClientExceptionInterface
    {
        return $this->exception;
    }
}
