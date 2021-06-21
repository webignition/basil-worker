<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\SourceCompilation\FailedEvent;
use App\Event\SourceCompilation\PassedEvent;
use App\Tests\Mock\Entity\MockCallback;
use App\Tests\Mock\MockSuiteManifest;
use App\Entity\Callback\CallbackInterface;

trait CreateFromCompilationPassedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromCompilationPassedEventDataProvider(): array
    {
        return [
            FailedEvent::class => [
                'event' => new PassedEvent(
                    '/app/source/test.yml',
                    (new MockSuiteManifest())->getMock()
                ),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_COMPILATION_PASSED)
                    ->withGetPayloadCall([
                        'source' => '/app/source/test.yml',
                    ])
                    ->getMock(),
            ],
        ];
    }
}
