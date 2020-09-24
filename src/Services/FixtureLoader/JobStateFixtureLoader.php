<?php

namespace App\Services\FixtureLoader;

use App\Entity\JobState;

class JobStateFixtureLoader extends AbstractStateFixtureLoader implements FixtureLoaderInterface
{
    protected function getEntityClass(): string
    {
        return JobState::class;
    }

    protected function createEntity(string $name): object
    {
        return JobState::create($name);
    }
}
