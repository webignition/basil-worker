<?php

declare(strict_types=1);

namespace App\Model\RunnerTest;

use App\Entity\TestConfiguration as TestConfigurationEntity;
use webignition\BasilRunnerDocuments\TestConfiguration;

class TestConfigurationProxy extends TestConfiguration
{
    public function __construct(TestConfigurationEntity $configurationEntity)
    {
        parent::__construct($configurationEntity->getBrowser(), $configurationEntity->getUrl());
    }
}
