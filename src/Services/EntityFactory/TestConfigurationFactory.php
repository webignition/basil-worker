<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Entity\TestConfiguration;

class TestConfigurationFactory extends AbstractEntityFactory
{
    public function create(string $browser, string $url): TestConfiguration
    {
        $configuration = TestConfiguration::create($browser, $url);

        $this->persist($configuration);

        return $configuration;
    }
}
