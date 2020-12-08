<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\CallbackResponseHandler;
use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class MockCallbackResponseHandler
{
    /**
     * @var CallbackResponseHandler|MockInterface
     */
    private CallbackResponseHandler $callbackResponseHandler;

    public function __construct()
    {
        $this->callbackResponseHandler = \Mockery::mock(CallbackResponseHandler::class);
    }

    public function getMock(): CallbackResponseHandler
    {
        return $this->callbackResponseHandler;
    }

    public function withHandleCall(CallbackInterface $callback, object $context): self
    {
        $this->callbackResponseHandler
            ->shouldReceive('handle')
            ->once()
            ->with($callback, $context);

        return $this;
    }

    public function withoutHandleCall(): self
    {
        $this->callbackResponseHandler
            ->shouldNotReceive('handle');

        return $this;
    }
}
