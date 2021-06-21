<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\ExecutionCompletedEvent;
use App\Tests\Mock\Entity\MockCallback;
use App\Entity\Callback\CallbackInterface;

trait CreateFromExecutionCompletedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromExecutionCompletedEventDataProvider(): array
    {
        return [
            ExecutionCompletedEvent::class => [
                'event' => new ExecutionCompletedEvent(),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_EXECUTION_COMPLETED)
                    ->withGetPayloadCall([])
                    ->getMock(),
            ],
        ];
    }
}
