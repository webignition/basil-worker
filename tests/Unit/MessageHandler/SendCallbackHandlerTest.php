<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Message\SendCallback;
use App\MessageHandler\SendCallbackHandler;
use App\Model\Callback\CallbackInterface;
use App\Tests\Mock\Services\MockCallbackSender;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SendCallbackHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testInvoke()
    {
        $callback = \Mockery::mock(CallbackInterface::class);
        $message = new SendCallback($callback);

        $callbackSender = (new MockCallbackSender())
            ->withSendCall($callback)
            ->getMock();

        $handler = new SendCallbackHandler($callbackSender);
        $handler($message);
    }
}
