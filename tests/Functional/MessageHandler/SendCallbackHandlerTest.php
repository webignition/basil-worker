<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Callback\CallbackInterface;
use App\Message\SendCallback;
use App\MessageHandler\SendCallbackHandler;
use App\Repository\CallbackRepository;
use App\Services\CallbackFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockCallbackSender;
use App\Tests\Mock\Services\MockCallbackStateMutator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;
use webignition\YamlDocument\Document;

class SendCallbackHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;
    use TestClassServicePropertyInjectorTrait;

    private SendCallbackHandler $handler;
    private CallbackFactory $callbackFactory;
    private CallbackRepository $callbackRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testInvokeCallbackNotExists()
    {
        $callback = \Mockery::mock(CallbackInterface::class);
        $callback
            ->shouldReceive('getId')
            ->andReturn(0);

        $stateMutator = (new MockCallbackStateMutator())
            ->withoutSetSendingCall()
            ->getMock();

        $sender = (new MockCallbackSender())
            ->withoutSendCall()
            ->getMock();

        ObjectReflector::setProperty($this->handler, SendCallbackHandler::class, 'stateMutator', $stateMutator);
        ObjectReflector::setProperty($this->handler, SendCallbackHandler::class, 'sender', $sender);

        $message = new SendCallback($callback);

        ($this->handler)($message);
    }

    public function testInvokeCallbackExists()
    {
        $document = new Document();
        $callback = $this->callbackFactory->createForExecuteDocumentReceived($document);

        $mockSender = new MockCallbackSender();

        $expectedSentCallback = $this->callbackRepository->find((int) $callback->getId());
        if ($expectedSentCallback instanceof CallbackInterface) {
            $expectedSentCallback->setState(CallbackInterface::STATE_SENDING);
            $mockSender = $mockSender->withSendCall($expectedSentCallback);
        }

        ObjectReflector::setProperty($this->handler, SendCallbackHandler::class, 'sender', $mockSender->getMock());

        $message = new SendCallback($callback);

        ($this->handler)($message);
    }
}
