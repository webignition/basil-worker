<?php

declare(strict_types=1);

namespace App\Services\FixtureLoader;

use Symfony\Component\Console\Output\OutputInterface;

interface FixtureLoaderInterface
{
    public function load(?OutputInterface $output = null): void;
}
