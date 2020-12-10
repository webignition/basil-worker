<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\SendCallback;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class SendCallbackTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSerializeDeserialize()
    {
        $callbackId = 9;

        $callback = \Mockery::mock(CallbackInterface::class);
        $callback
            ->shouldReceive('getId')
            ->andReturn($callbackId);

        $message = new SendCallback($callback);

        self::assertEquals(
            $message,
            unserialize(serialize($message))
        );
    }
}
