<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Event\CallbackHttpResponseEvent;
use App\Services\CallbackSender;
use App\Services\JobStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Model\Callback\MockCallback;
use App\Tests\Services\CallbackHttpExceptionEventSubscriber;
use App\Tests\Services\CallbackHttpResponseEventSubscriber;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class CallbackSenderTest extends AbstractBaseFunctionalTest
{
    private CallbackSender $callbackSender;
    private MockHandler $mockHandler;
    private CallbackHttpExceptionEventSubscriber $exceptionEventSubscriber;
    private CallbackHttpResponseEventSubscriber $responseEventSubscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $callbackSender = self::$container->get(CallbackSender::class);
        if ($callbackSender instanceof CallbackSender) {
            $this->callbackSender = $callbackSender;
        }

        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }

        $exceptionEventSubscriber = self::$container->get(CallbackHttpExceptionEventSubscriber::class);
        if ($exceptionEventSubscriber instanceof CallbackHttpExceptionEventSubscriber) {
            $this->exceptionEventSubscriber = $exceptionEventSubscriber;
        }

        $responseEventSubscriber = self::$container->get(CallbackHttpResponseEventSubscriber::class);
        if ($responseEventSubscriber instanceof CallbackHttpResponseEventSubscriber) {
            $this->responseEventSubscriber = $responseEventSubscriber;
        }
    }

    public function testSendSuccess()
    {
        $this->mockHandler->append(new Response());

        $result = $this->callbackSender->send(MockCallback::createEmpty());

        self::assertTrue($result);
        self::assertNull($this->exceptionEventSubscriber->getEvent());
        self::assertNull($this->responseEventSubscriber->getEvent());
    }

    /**
     * @dataProvider sendNonSuccessResponseDataProvider
     *
     * @param array<ResponseInterface|\Throwable|PromiseInterface|callable> $httpFixtures
     */
    public function testSendFailureNonSuccessResponse(array $httpFixtures)
    {
        $this->mockHandler->append(...$httpFixtures);

        $callback = MockCallback::createEmpty();

        $result = $this->callbackSender->send($callback);
        self::assertFalse($result);

        self::assertNull($this->exceptionEventSubscriber->getEvent());

        $event = $this->responseEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpResponseEvent::class, $event);

        self::assertSame($callback, $event->getCallback());
        self::assertSame(array_pop($httpFixtures), $event->getResponse());
    }

    public function sendNonSuccessResponseDataProvider(): array
    {
        return [
            'HTTP 400' => [
                'httpFixtures' => [
                    new Response(400),
                ],
            ],
        ];
    }
}
