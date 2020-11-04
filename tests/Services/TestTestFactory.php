<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\TestFactory;

class TestTestFactory extends TestFactory
{
    public function createFoo(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): Test {
        return parent::create($configuration, $source, $target, $stepCount);
    }
}
