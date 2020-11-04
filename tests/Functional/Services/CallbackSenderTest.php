<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\CallbackResponseHandler;
use App\Services\CallbackSender;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Model\Callback\MockCallback;
use App\Tests\Mock\Services\MockCallbackResponseHandler;
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
     * @dataProvider handleResponseReceivedDataProvider
     */
    public function testHandleResponseReceived(ResponseInterface $response)
    {
        $callback = MockCallback::createEmpty();

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

    public function handleResponseReceivedDataProvider(): array
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

        $this->callbackSender->send(MockCallback::createEmpty());
    }

    /**
     * @dataProvider sendFailureHttpClientExceptionThrownDataProvider
     */
    public function testSendFailureHttpClientExceptionThrown(ClientExceptionInterface $exception)
    {
        $this->createJob();

        $this->mockHandler->append($exception);

        $callback = MockCallback::createEmpty();

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
