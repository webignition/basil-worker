<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\TestSetup;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\TestFactory;

class TestTestFactory
{
    public function __construct(
        private TestFactory $testFactory,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function create(TestSetup $testSetup): Test
    {
        $test = $this->testFactory->create(
            $testSetup->getConfiguration(),
            $testSetup->getSource(),
            $testSetup->getTarget(),
            $testSetup->getStepCount()
        );

        $test->setState($testSetup->getState());

        $this->entityManager->flush();

        return $test;
    }
}
