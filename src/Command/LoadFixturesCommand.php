<?php

namespace App\Command;

use App\Services\FixtureLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends Command
{
    private FixtureLoader $fixtureLoader;

    public function __construct(FixtureLoader $fixtureLoader, $name = null)
    {
        parent::__construct($name);

        $this->fixtureLoader = $fixtureLoader;
    }

    protected function configure(): void
    {
        $this
            ->setName('basil-worker:load-fixtures')
            ->setDescription('Load basic data required for service to operate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fixtureLoader->load($output);
        $output->writeln('');

        return self::SUCCESS;
    }
}
