<?php

declare(strict_types=1);

namespace App\Model\RunnerTest;

use webignition\BasilRunnerDocuments\TestConfiguration;
use webignition\BasilWorker\PersistenceBundle\Entity\TestConfiguration as TestConfigurationEntity;

class TestConfigurationProxy extends TestConfiguration
{
    public function __construct(TestConfigurationEntity $configurationEntity)
    {
        parent::__construct($configurationEntity->getBrowser(), $configurationEntity->getUrl());
    }
}
