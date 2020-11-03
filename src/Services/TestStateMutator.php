<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;

class TestStateMutator
{
    private TestStore $testStore;

    public function __construct(TestStore $testStore)
    {
        $this->testStore = $testStore;
    }

    public function setRunning(Test $test): void
    {
        $this->set($test, Test::STATE_RUNNING);
    }

    public function setComplete(Test $test): void
    {
        $this->set($test, Test::STATE_COMPLETE);
    }

    public function setFailed(Test $test): void
    {
        $this->set($test, Test::STATE_FAILED);
    }

    /**
     * @param Test $test
     * @param Test::STATE_* $state
     */
    private function set(Test $test, string $state): void
    {
        $test->setState($state);
        $this->testStore->store($test);
    }
}
