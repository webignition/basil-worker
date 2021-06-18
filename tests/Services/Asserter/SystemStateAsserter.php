<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter;

use App\Tests\Services\EntityRefresher;
use PHPUnit\Framework\TestCase;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;

class SystemStateAsserter
{
    public function __construct(
        private CompilationState $compilationState,
        private ExecutionState $executionState,
        private ApplicationState $applicationState,
        private EntityRefresher $entityRefresher,
    ) {
    }

    /**
     * @param CompilationState::STATE_* $expectedCompilationState
     * @param ExecutionState::STATE_*   $expectedExecutionState
     * @param ApplicationState::STATE_* $expectedApplicationState
     */
    public function assertSystemState(
        string $expectedCompilationState,
        string $expectedExecutionState,
        string $expectedApplicationState
    ): void {
        $this->entityRefresher->refresh();

        TestCase::assertSame($expectedCompilationState, (string) $this->compilationState);
        TestCase::assertSame($expectedExecutionState, (string) $this->executionState);
        TestCase::assertSame($expectedApplicationState, (string) $this->applicationState);
    }
}
