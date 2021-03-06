<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Entity\Callback\CallbackInterface;
use App\Event\TestFailedEvent;
use App\Event\TestPassedEvent;
use App\Event\TestStartedEvent;
use App\Event\TestStepFailedEvent;
use App\Event\TestStepPassedEvent;
use App\Tests\Mock\Entity\MockCallback;
use App\Tests\Mock\Entity\MockTest;
use webignition\YamlDocument\Document;

trait CreateFromTestEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromTestEventEventDataProvider(): array
    {
        $documentData = [
            'document-key' => 'document-value',
        ];

        $document = new Document((string) json_encode($documentData));

        return [
            TestStartedEvent::class => [
                'event' => new TestStartedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_TEST_STARTED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestStepPassedEvent::class => [
                'event' => new TestStepPassedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_STEP_PASSED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestStepFailedEvent::class => [
                'event' => new TestStepFailedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_STEP_FAILED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestPassedEvent::class => [
                'event' => new TestPassedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_TEST_PASSED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
            TestFailedEvent::class => [
                'event' => new TestFailedEvent((new MockTest())->getMock(), $document),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_TEST_FAILED)
                    ->withGetPayloadCall($documentData)
                    ->getMock(),
            ],
        ];
    }
}
