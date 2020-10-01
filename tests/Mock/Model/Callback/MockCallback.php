<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model\Callback;

use App\Model\Callback\CallbackInterface;
use Mockery\MockInterface;

class MockCallback
{
    /**
     * @var CallbackInterface|MockInterface
     */
    private CallbackInterface $callback;

    public function __construct()
    {
        $this->callback = \Mockery::mock(CallbackInterface::class);
    }

    public static function createEmpty(): CallbackInterface
    {
        return (new MockCallback())
            ->withGetTypeCall('')
            ->withGetDataCall([])
            ->getMock();
    }

    public function getMock(): CallbackInterface
    {
        return $this->callback;
    }

    public function withGetTypeCall(string $type): self
    {
        $this->callback
            ->shouldReceive('getType')
            ->andReturn($type);

        return $this;
    }

    /**
     * @param array<mixed> $data
     *
     * @return $this
     */
    public function withGetDataCall(array $data): self
    {
        $this->callback
            ->shouldReceive('getData')
            ->andReturn($data);

        return $this;
    }
}
