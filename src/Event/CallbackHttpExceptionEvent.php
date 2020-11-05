<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;

class CallbackHttpExceptionEvent extends AbstractCallbackEvent
{
    private ClientExceptionInterface $exception;

    public function __construct(CallbackInterface $callback, ClientExceptionInterface $exception)
    {
        parent::__construct($callback);
        $this->exception = $exception;
    }

    public function getException(): ClientExceptionInterface
    {
        return $this->exception;
    }
}
