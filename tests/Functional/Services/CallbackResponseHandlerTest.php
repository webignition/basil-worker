<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Callback\DelayedCallback;
use App\Event\CallbackHttpErrorEvent;
use App\Services\CallbackResponseHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\TestCallback;
use App\Tests\Services\CallbackHttpErrorEventSubscriber;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackResponseHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private CallbackResponseHandler $callbackResponseHandler;
    private CallbackHttpErrorEventSubscriber $httpErrorEventSubscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    /**
     * @dataProvider handleDataProvider
     *
     * @param ClientExceptionInterface|ResponseInterface $context
     */
    public function testHandle(object $context)
    {
        $callback = new TestCallback();
        self::assertSame(0, $callback->getRetryCount());

        $this->callbackResponseHandler->handle($callback, $context);

        $event = $this->httpErrorEventSubscriber->getEvent();
        self::assertInstanceOf(CallbackHttpErrorEvent::class, $event);

        $eventCallback = $event->getCallback();
        self::assertInstanceOf(DelayedCallback::class, $eventCallback);
        self::assertSame($eventCallback->getEntity(), $callback->getEntity());
        self::assertSame($context, $event->getContext());
        self::assertSame(1, $callback->getRetryCount());
    }

    public function handleDataProvider(): array
    {
        return [
            'non-success response' => [
                'context' => new Response(404),
            ],
            'exception' => [
                'context' => \Mockery::mock(ConnectException::class),
            ],
        ];
    }
}
