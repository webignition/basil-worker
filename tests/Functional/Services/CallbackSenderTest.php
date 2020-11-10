<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackResponseHandler;
use App\Services\CallbackSender;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockCallbackResponseHandler;
use App\Tests\Model\TestCallback;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class CallbackSenderTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CallbackSender $callbackSender;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $callbackSender = self::$container->get(CallbackSender::class);
        if ($callbackSender instanceof CallbackSender) {
            $this->callbackSender = $callbackSender;
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    /**
     * @dataProvider sendResponseReceivedDataProvider
     */
    public function testSendResponseReceived(ResponseInterface $response)
    {
        $callback = new TestCallback();

        $this->mockHandler->append($response);

        $responseHandler = (new MockCallbackResponseHandler())
            ->withHandleResponseCall($callback, $response)
            ->withoutHandleClientExceptionCall()
            ->getMock();

        $this->createJob();
        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->mockHandler->append($response);
        $this->callbackSender->send($callback);
    }

    public function sendResponseReceivedDataProvider(): array
    {
        return [
            'HTTP 200' => [
                'response' => new Response(200),
            ],
            'HTTP 400' => [
                'response' => new Response(400),
            ],
        ];
    }

    public function testSendNoJob()
    {
        $responseHandler = (new MockCallbackResponseHandler())
            ->withoutHandleResponseCall()
            ->withoutHandleClientExceptionCall()
            ->getMock();

        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->callbackSender->send(new TestCallback());
    }

    public function testSendCallbackRetryLimitReached()
    {
        $this->createJob();

        $retryLimit = (int) self::$container->getParameter('callback_retry_limit');

        $responseHandler = (new MockCallbackResponseHandler())
            ->withoutHandleResponseCall()
            ->withoutHandleClientExceptionCall()
            ->getMock();

        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->callbackSender->send((new TestCallback())->withRetryCount($retryLimit));
    }

    /**
     * @dataProvider sendFailureHttpClientExceptionThrownDataProvider
     */
    public function testSendFailureHttpClientExceptionThrown(ClientExceptionInterface $exception)
    {
        $this->createJob();

        $this->mockHandler->append($exception);

        $callback = new TestCallback();

        $responseHandler = (new MockCallbackResponseHandler())
            ->withoutHandleResponseCall()
            ->withHandleClientExceptionCall($callback, $exception)
            ->getMock();

        $this->setCallbackResponseHandlerOnCallbackSender($responseHandler);

        $this->callbackSender->send($callback);
    }

    public function sendFailureHttpClientExceptionThrownDataProvider(): array
    {
        return [
            'Guzzle ConnectException' => [
                'exception' => \Mockery::mock(ConnectException::class),
            ],
        ];
    }

    private function createJob(): void
    {
        $jobStore = self::$container->get(JobStore::class);
        if ($jobStore instanceof JobStore) {
            $jobStore->create('label content', 'http://example.com/callback');
        }
    }

    private function setCallbackResponseHandlerOnCallbackSender(CallbackResponseHandler $responseHandler): void
    {
        ObjectReflector::setProperty(
            $this->callbackSender,
            CallbackSender::class,
            'callbackResponseHandler',
            $responseHandler
        );
    }
}
