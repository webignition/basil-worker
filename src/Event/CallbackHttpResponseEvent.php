<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\CallbackInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CallbackHttpResponseEvent extends Event
{
    public const NAME = 'worker.callback.http-response';

    private CallbackInterface $callback;
    private ResponseInterface $response;

    public function __construct(CallbackInterface $callback, ResponseInterface $response)
    {
        $this->callback = $callback;
        $this->response = $response;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
