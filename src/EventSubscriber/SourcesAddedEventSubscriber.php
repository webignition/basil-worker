<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourcesAddedEvent;
use App\Message\CompileSource;
use App\Services\JobSourceFinder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SourcesAddedEventSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;
    private JobSourceFinder $jobSourceFinder;

    public function __construct(MessageBusInterface $messageBus, JobSourceFinder $jobSourceFinder)
    {
        $this->messageBus = $messageBus;
        $this->jobSourceFinder = $jobSourceFinder;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourcesAddedEvent::NAME => 'onSourcesAdded',
        ];
    }

    public function onSourcesAdded(): void
    {
        $nextNonCompiledSource = $this->jobSourceFinder->findNextNonCompiledSource();

        if (is_string($nextNonCompiledSource)) {
            $message = new CompileSource($nextNonCompiledSource);
            $this->messageBus->dispatch($message);

            return;
        }
    }
}
