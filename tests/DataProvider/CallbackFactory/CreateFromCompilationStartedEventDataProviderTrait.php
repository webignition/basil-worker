<?php

declare(strict_types=1);

namespace App\Tests\DataProvider\CallbackFactory;

use App\Event\SourceCompilation\StartedEvent;
use App\Tests\Mock\Entity\MockCallback;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

trait CreateFromCompilationStartedEventDataProviderTrait
{
    /**
     * @return array[]
     */
    public function createFromCompilationStartedEventDataProvider(): array
    {
        return [
            StartedEvent::class => [
                'event' => new StartedEvent('/app/source/test.yml'),
                'expectedCallback' => (new MockCallback())
                    ->withGetTypeCall(CallbackInterface::TYPE_COMPILATION_STARTED)
                    ->withGetPayloadCall([
                        'source' => '/app/source/test.yml',
                    ])
                    ->getMock(),
            ],
        ];
    }
}
