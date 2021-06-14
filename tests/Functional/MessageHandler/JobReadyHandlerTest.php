<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Event\JobReadyEvent;
use App\Message\JobReadyMessage;
use App\MessageHandler\JobReadyHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockEventDispatcher;
use App\Tests\Model\ExpectedDispatchedEvent;
use App\Tests\Model\ExpectedDispatchedEventCollection;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class JobReadyHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private JobReadyHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $jobReadyHandler = self::$container->get(JobReadyHandler::class);
        \assert($jobReadyHandler instanceof JobReadyHandler);
        $this->handler = $jobReadyHandler;
    }

    public function testInvoke(): void
    {
        $eventDispatcher = (new MockEventDispatcher())
            ->withDispatchCalls(new ExpectedDispatchedEventCollection([
                new ExpectedDispatchedEvent(
                    function (JobReadyEvent $actualEvent) {
                        self::assertInstanceOf(JobReadyEvent::class, $actualEvent);

                        return true;
                    },
                ),
            ]))
            ->getMock()
        ;

        ObjectReflector::setProperty($this->handler, JobReadyHandler::class, 'eventDispatcher', $eventDispatcher);

        $message = new JobReadyMessage();

        ($this->handler)($message);
    }
}
