<?php

declare(strict_types=1);

namespace App\Model\RunnerTest;

use App\Entity\Test as TestEntity;
use webignition\BasilRunnerDocuments\Test;

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
