<?php

declare(strict_types=1);

namespace App\Model\RunnerTest;

use webignition\BasilRunnerDocuments\Test;
use webignition\BasilWorker\PersistenceBundle\Entity\Test as TestEntity;

class TestProxy extends Test
{
    public function __construct(TestEntity $testEntity)
    {
        parent::__construct(
            (string) $testEntity->getSource(),
            new TestConfigurationProxy($testEntity->getConfiguration())
        );
    }
}
