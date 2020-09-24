<?php

declare(strict_types=1);

namespace App\Services\FixtureLoader;

use App\Entity\TestState;

class TestStateFixtureLoader extends AbstractStateFixtureLoader implements FixtureLoaderInterface
{
    protected function getEntityClass(): string
    {
        return TestState::class;
    }

    protected function createEntity(string $name): object
    {
        return TestState::create($name);
    }
}
