<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\TestRepository;

class TestCanceller
{
    private TestStateMutator $testStateMutator;
    private TestRepository $testRepository;

    public function __construct(TestStateMutator $testStateMutator, TestRepository $testRepository)
    {
        $this->testStateMutator = $testStateMutator;
        $this->testRepository = $testRepository;
    }

    public function cancelAwaiting(): void
    {
        $awaitingTests = $this->testRepository->findAllAwaiting();

        foreach ($awaitingTests as $awaitingTest) {
            $this->testStateMutator->setCancelled($awaitingTest);
        }
    }
}
