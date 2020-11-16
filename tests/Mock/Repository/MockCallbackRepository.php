<?php

declare(strict_types=1);

namespace App\Tests\Mock\Repository;

use App\Entity\Callback\CallbackInterface;
use App\Repository\CallbackRepository;
use Mockery\MockInterface;

class MockCallbackRepository
{
    /**
     * @var CallbackRepository|MockInterface
     */
    private CallbackRepository $callbackRepository;

    public function __construct()
    {
        $this->callbackRepository = \Mockery::mock(CallbackRepository::class);
    }

    public function getMock(): CallbackRepository
    {
        return $this->callbackRepository;
    }

    public function withFindCall(int $testId, ?CallbackInterface $callback): self
    {
        $this->callbackRepository
            ->shouldReceive('find')
            ->with($testId)
            ->andReturn($callback);

        return $this;
    }
}
