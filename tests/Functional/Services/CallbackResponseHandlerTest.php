<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Message\SendCallback;
use App\Model\Callback\CallbackInterface;
use App\Services\CallbackResponseHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class CallbackResponseHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CallbackResponseHandler $callbackResponseHandler;
    private InMemoryTransport $messengerTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $callbackResponseHandler = self::$container->get(CallbackResponseHandler::class);
        self::assertInstanceOf(CallbackResponseHandler::class, $callbackResponseHandler);
        if ($callbackResponseHandler instanceof CallbackResponseHandler) {
            $this->callbackResponseHandler = $callbackResponseHandler;
        }

        $messengerTransport = self::$container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $messengerTransport);
        if ($messengerTransport instanceof InMemoryTransport) {
            $this->messengerTransport = $messengerTransport;
        }
    }

    /**
     * @dataProvider handleResponseNoMessageDispatchedDataProvider
     */
    public function testHandleResponseNoMessageDispatched(CallbackInterface $callback, ResponseInterface $response)
    {
        self::assertCount(0, $this->messengerTransport->get());

        $this->callbackResponseHandler->handleResponse($callback, $response);

        self::assertCount(0, $this->messengerTransport->get());
    }

    public function handleResponseNoMessageDispatchedDataProvider(): array
    {
        $dataSets = [];

        for ($statusCode = 100; $statusCode < 300; $statusCode++) {
            $dataSets[(string) $statusCode] = [
                'callback' => new TestCallback(),
                'response' => new Response($statusCode),
            ];
        }

        return $dataSets;
    }

    public function testHandleResponseMessageDispatched()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $response = new Response(404);
        $callback = new TestCallback();
        self::assertSame(0, $callback->getRetryCount());

        $this->callbackResponseHandler->handleResponse($callback, $response);

        $this->assertMessageTransportQueue($callback);
    }

    public function testHandleExceptionMessageDispatched()
    {
        self::assertCount(0, $this->messengerTransport->get());

        $callback = new TestCallback();
        self::assertSame(0, $callback->getRetryCount());

        $this->callbackResponseHandler->handleClientException($callback);

        $this->assertMessageTransportQueue($callback);
    }

    private function assertMessageTransportQueue(CallbackInterface $expectedCallback): void
    {
        $queue = $this->messengerTransport->get();
        self::assertCount(1, $queue);
        self::assertIsArray($queue);

        $expectedQueuedMessage = new SendCallback($expectedCallback);

        self::assertEquals($expectedQueuedMessage, $queue[0]->getMessage());
    }
}
