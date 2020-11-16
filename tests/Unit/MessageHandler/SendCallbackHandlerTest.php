<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Callback\CallbackInterface;
use App\Message\SendCallback;
use App\MessageHandler\SendCallbackHandler;
use App\Tests\Mock\Repository\MockCallbackRepository;
use App\Tests\Mock\Services\MockCallbackSender;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SendCallbackHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

//    public function testInvoke()
//    {
//        $callback = \Mockery::mock(CallbackInterface::class);
//        $callback
//            ->shouldReceive('getId')
//            ->andReturn(1);
//
//        $message = new SendCallback($callback);
//
//        $callbackSender = (new MockCallbackSender())
//            ->withSendCall($callback)
//            ->getMock();
//
//        $handler = new SendCallbackHandler($callbackSender);
//        $handler($message);
//    }

    public function testInvokeCallbackNotExists()
    {
        $callback = \Mockery::mock(CallbackInterface::class);
        $callback
            ->shouldReceive('getId')
            ->andReturn(0);

        $message = new SendCallback($callback);

        $callbackSender = (new MockCallbackSender())
            ->withoutSendCall()
            ->getMock();

        $callbackRepository = (new MockCallbackRepository())
            ->withFindCall(0, null)
            ->getMock();

        $handler = new SendCallbackHandler($callbackSender, $callbackRepository);
        $handler($message);
    }

    public function testInvokeCallbackExists()
    {
        $callback = \Mockery::mock(CallbackInterface::class);
        $callback
            ->shouldReceive('getId')
            ->andReturn(0);

        $message = new SendCallback($callback);

        $callbackSender = (new MockCallbackSender())
            ->withSendCall($callback)
            ->getMock();

        $callbackRepository = (new MockCallbackRepository())
            ->withFindCall(0, $callback)
            ->getMock();

        $handler = new SendCallbackHandler($callbackSender, $callbackRepository);
        $handler($message);
    }
}
