<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\Callback\CallbackInterface;
use App\Services\CallbackSender;
use App\Tests\Mock\Services\MockJobStore;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CallbackSenderTest extends TestCase
{
    public function testSendWithNoJob()
    {
        $jobStore = (new MockJobStore())
            ->withHasJobCall(false)
            ->getMock();

        $callbackSender = new CallbackSender(
            \Mockery::mock(ClientInterface::class),
            $jobStore,
            \Mockery::mock(EventDispatcherInterface::class)
        );

        self::assertFalse(
            $callbackSender->send(\Mockery::mock(CallbackInterface::class))
        );
    }
}
