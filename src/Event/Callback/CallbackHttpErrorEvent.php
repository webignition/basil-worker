<?php

declare(strict_types=1);

namespace App\Event\Callback;

use App\Entity\Callback\CallbackInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class CallbackHttpErrorEvent extends AbstractCallbackEvent
{
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
        parent::__construct($callback);

        if ($context instanceof ClientExceptionInterface || $context instanceof ResponseInterface) {
            $this->context = $context;
        }
    }

    public function getContext(): ?object
    {
        return $this->context;
    }
}
