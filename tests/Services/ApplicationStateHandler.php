<?php

declare(strict_types=1);

namespace App\Tests\Services;

use webignition\BasilWorker\StateBundle\Services\ApplicationState;

class ApplicationStateHandler
{
    private const MAX_DURATION_IN_SECONDS = 30;
    private const MICROSECONDS_PER_SECOND = 1000000;

    public function __construct(
        private ApplicationState $applicationState,
        private EntityRefresher $entityRefresher,
    ) {
    }

    /**
     * @param array<ApplicationState::STATE_*> $states
     */
    public function waitUntilStateIs(array $states): bool
    {
        $duration = 0;
        $maxDuration = self::MAX_DURATION_IN_SECONDS * self::MICROSECONDS_PER_SECOND;
        $maxDurationReached = false;
        $intervalInMicroseconds = 100000;

        while (
            false === in_array((string) $this->applicationState, $states)
            && false === $maxDurationReached
        ) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;
            $maxDurationReached = $duration >= $maxDuration;

            if ($maxDurationReached) {
                return false;
            }

            $this->entityRefresher->refresh();
        }

        return true;
    }
}
