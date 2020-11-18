<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CallbackHttpErrorEvent extends Event implements CallbackEventInterface
{
    private CallbackInterface $callback;

    /**
     * @var ClientExceptionInterface|ResponseInterface
     */
    private ?object $context;

    /**
     * @param CallbackInterface $callback
     * @param object $context
     */
    public function __construct(CallbackInterface $callback, object $context)
    {
        $this->callback = $callback;

        if ($context instanceof ClientExceptionInterface || $context instanceof ResponseInterface) {
            $this->context = $context;
        }
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }

    public function getContext(): ?object
    {
        return $this->context;
    }
}
