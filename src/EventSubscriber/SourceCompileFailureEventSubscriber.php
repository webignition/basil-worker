<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SourceCompile\SourceCompileFailureEvent;
use App\Message\SendCallback;
use App\Model\Callback\CompileFailure;
use App\Services\JobStateMutator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SourceCompileFailureEventSubscriber implements EventSubscriberInterface
{
    private JobStateMutator $jobStateMutator;
    private MessageBusInterface $messageBus;

    public function __construct(JobStateMutator $jobStateMutator, MessageBusInterface $messageBus)
    {
        $this->jobStateMutator = $jobStateMutator;
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            SourceCompileFailureEvent::class => [
                ['setJobState', 10],
                ['dispatchSendCallbackMessage', 0],
            ],
        ];
    }

    public function setJobState(): void
    {
        $this->jobStateMutator->setCompilationFailed();
    }

    public function dispatchSendCallbackMessage(SourceCompileFailureEvent $event): void
    {
        $callback = new CompileFailure($event->getOutput());
        $message = new SendCallback($callback);

        $this->messageBus->dispatch($message);
    }
}
