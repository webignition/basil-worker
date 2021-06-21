<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter;

use App\Entity\Test;
use App\Repository\TestRepository;
use PHPUnit\Framework\TestCase;

class TestEntityAsserter
{
    public function __construct(
        private TestRepository $repository
    ) {
    }

    /**
     * @param array<Test::STATE_*> $expectedStates
     */
    public function assertTestStates(array $expectedStates): void
    {
        $tests = $this->repository->findAll();
        $states = [];

        foreach ($tests as $test) {
            $states[] = $test->getState();
        }

        TestCase::assertSame($expectedStates, $states);
    }

    public function dumpAll(): void
    {
        var_dump($this->repository->findAll());
    }
}
