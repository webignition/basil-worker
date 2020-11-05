<?php

declare(strict_types=1);

namespace App\Event;

use App\Model\Callback\CallbackInterface;
use Psr\Http\Message\ResponseInterface;

class CallbackHttpResponseEvent extends AbstractCallbackEvent
{
    private ResponseInterface $response;

    public function __construct(CallbackInterface $callback, ResponseInterface $response)
    {
        parent::__construct($callback);
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
