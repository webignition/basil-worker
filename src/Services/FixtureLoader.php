<?php

namespace App\Services;

use App\Services\FixtureLoader\FixtureLoaderInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixtureLoader
{
    /**
     * @var FixtureLoaderInterface[]
     */
    private array $fixtureLoaders;

    /**
     * @param array<mixed> $fixtureLoaders
     */
    public function __construct(array $fixtureLoaders)
    {
        $this->fixtureLoaders = array_filter($fixtureLoaders, function ($item) {
            return $item instanceof FixtureLoaderInterface;
        });
    }

    public function load(?OutputInterface $output = null): void
    {
        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->load($output);
        }
    }
}
