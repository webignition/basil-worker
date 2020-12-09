<?php

declare(strict_types=1);

namespace App\Tests\Services;

use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;

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
