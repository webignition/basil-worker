<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Yaml\Parser;
use webignition\BasilCompilerModels\TestManifest;

class TestStore
{
    private EntityManagerInterface $entityManager;
    private Parser $yamlParser;

    /**
     * @var EntityRepository<Test>
     */
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, Parser $yamlParser)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Test::class);
        $this->yamlParser = $yamlParser;
    }

    public function find(int $testId): ?Test
    {
        return $this->hydrateManifestIfTest(
            $this->repository->find($testId)
        );
    }

    /**
     * @return Test[]
     */
    public function findAll(): array
    {
        $all = $this->repository->findBy([], [
            'position' => 'ASC',
        ]);

        foreach ($all as $testIndex => $test) {
            $test = $this->hydrateManifestIfTest($test);

            if ($test instanceof Test) {
                $all[$testIndex] = $test;
            }
        }

        return $all;
    }

    public function findBySource(string $source): ?Test
    {
        return $this->hydrateManifestIfTest(
            $this->repository->findOneBy([
                'source' => $source,
            ])
        );
    }

    public function create(string $source, string $manifestPath): Test
    {
        $position = $this->findNextPosition();
        $test = Test::create($source, $manifestPath, $position);

        return $this->store($test);
    }

    public function createFromTestManifest(TestManifest $manifest, string $manifestPath): Test
    {
        $test = $this->create($manifest->getSource(), $manifestPath);
        $test->setManifest($manifest);

        return $test;
    }

    public function store(Test $test): Test
    {
        $this->entityManager->persist($test);
        $this->entityManager->flush();

        return $test;
    }

    public function findNextAwaiting(): ?Test
    {
        return $this->hydrateManifestIfTest($this->repository->findOneBy(
            [
                'state' => Test::STATE_AWAITING,
            ],
            [
                'position' => 'ASC',
            ]
        ));
    }

    public function loadManifest(Test $test): void
    {
        $manifestContent = (string) file_get_contents($test->getManifestPath());
        $manifestData = $this->yamlParser->parse($manifestContent);

        $manifest = TestManifest::fromArray($manifestData);
        $test->setManifest($manifest);
    }

    private function findNextPosition(): int
    {
        $maxPosition = $this->findMaxPosition();

        return null === $maxPosition
            ? 1
            : $maxPosition + 1;
    }

    private function findMaxPosition(): ?int
    {
        $test = $this->repository->findOneBy([], [
            'position' => 'DESC',
        ]);

        return $test instanceof Test
            ? $test->getPosition()
            : null;
    }

    private function hydrateManifestIfTest(?Test $test): ?Test
    {
        if ($test instanceof Test) {
            $this->loadManifest($test);
        }

        return $test;
    }
}
