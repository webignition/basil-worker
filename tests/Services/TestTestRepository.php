<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Test;
use App\Repository\TestRepository;

class TestTestRepository extends TestRepository
{
    /**
     * @return array<Test::STATE_*>
     */
    public function getStates(): array
    {
        $states = [];
        foreach ($this->findAll() as $test) {
            $states[] = $test->getState();
        }

        return $states;
    }
}
