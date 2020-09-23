<?php

namespace App\Services\FixtureLoader;

use App\Entity\JobState;
use App\Services\DataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobStateFixtureLoader extends AbstractFixtureLoader implements FixtureLoaderInterface
{
    public function __construct(EntityManagerInterface $entityManager, DataProviderInterface $dataProvider)
    {
        parent::__construct($entityManager, $dataProvider);
    }

    protected function getEntityClass(): string
    {
        return JobState::class;
    }

    public function load(?OutputInterface $output = null): void
    {
        if ($output) {
            $output->writeln('Importing job states ...');
        }

        foreach ($this->data as $name) {
            if ($output) {
                $output->write(sprintf(
                    '  ' . '<comment>%s</comment> %s...',
                    $name,
                    $this->createPostNamePadding($this->data, $name)
                ));
            }

            $entity = $this->repository->findOneBy([
                'name' => $name,
            ]);

            if (!$entity) {
                if ($output) {
                    $output->write(' <fg=cyan>creating</>');
                }

                $entity = JobState::create($name);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
            }

            if ($output) {
                $output->writeln(' <info>✓</info>');
            }
        }

        if ($output) {
            $output->writeln('');
        }
    }

    /**
     * @param string[] $names
     * @param string $name
     *
     * @return string
     */
    private function createPostNamePadding(array $names, string $name): string
    {
        $lengths = array_map(fn (string $name) => strlen($name), $names);

        $max = max($lengths);
        $difference = $max - strlen($name);

        return str_repeat(' ', $difference);
    }
}
