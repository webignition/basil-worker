<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\TestSetup;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use App\Services\EntityFactory\TestFactory;

class TestTestFactory
{
    public function __construct(
        private TestFactory $testFactory,
        private EntityManagerInterface $entityManager,
        private string $compilerSourceDirectory,
    ) {
    }

    public function create(TestSetup $testSetup): Test
    {
        $source = $testSetup->getSource();
        $source = str_replace('{{ compiler_source_directory }}', $this->compilerSourceDirectory, $source);

        $test = $this->testFactory->create(
            $testSetup->getConfiguration(),
            $source,
            $testSetup->getTarget(),
            $testSetup->getStepCount()
        );

        $test->setState($testSetup->getState());

        $this->entityManager->flush();

        return $test;
    }
}
