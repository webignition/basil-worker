<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\CallbackStateMutator;

class MockCallbackStateMutator
{
    /**
     * @var CallbackStateMutator|MockInterface
     */
    private CallbackStateMutator $callbackStateMutator;

    public function __construct()
    {
        $this->callbackStateMutator = \Mockery::mock(CallbackStateMutator::class);
    }

    public function getMock(): CallbackStateMutator
    {
        return $this->callbackStateMutator;
    }

    public function withoutSetSendingCall(): self
    {
        $this->callbackStateMutator
            ->shouldNotReceive('setSending');

        return $this;
    }

    public function withSetSendingCall(CallbackInterface $callback): self
    {
        $this->callbackStateMutator
            ->shouldReceive('setSending')
            ->with($callback);

        return $this;
    }
}
