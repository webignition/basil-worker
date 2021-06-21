<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Event\JobCompletedEvent;
use App\Message\JobCompletedCheckMessage;
use App\Services\ApplicationState;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class JobCompletedCheckHandler implements MessageHandlerInterface
{
    public function __construct(
        private ApplicationState $applicationState,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(JobCompletedCheckMessage $jobCompleteCheckMessage): void
    {
        if ($this->applicationState->is(ApplicationState::STATE_COMPLETE)) {
            $this->eventDispatcher->dispatch(new JobCompletedEvent());
        }
    }
}
