<?php

namespace App\Repository;

use App\Entity\Test;
use App\Services\SourcePathTranslator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Test|null find($id, $lockMode = null, $lockVersion = null)
 * @method Test|null findOneBy(array $criteria, array $orderBy = null)
 * @method Test[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<Test>
 */
class TestRepository extends ServiceEntityRepository
{
    private SourcePathTranslator $sourcePathTranslator;

    public function __construct(ManagerRegistry $registry, SourcePathTranslator $sourcePathTranslator)
    {
        parent::__construct($registry, Test::class);

        $this->sourcePathTranslator = $sourcePathTranslator;
    }

    /**
     * @return Test[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'position' => 'ASC',
        ]);
    }

    public function findBySource(string $source): ?Test
    {
        return $this->findOneBy([
            'source' => $source,
        ]);
    }

    public function findMaxPosition(): ?int
    {
        $test = $this->findOneBy([], [
            'position' => 'DESC',
        ]);

        return $test instanceof Test
            ? $test->getPosition()
            : null;
    }

    public function findNextAwaiting(): ?Test
    {
        $test = $this->findOneBy(
            [
                'state' => Test::STATE_AWAITING,
            ],
            [
                'position' => 'ASC',
            ]
        );

        return $test instanceof Test ? $test : null;
    }

    /**
     * @return Test[]
     */
    public function findAllAwaiting(): array
    {
        return $this->findBy([
            'state' => Test::STATE_AWAITING,
        ]);
    }

    /**
     * @return Test[]
     */
    public function findAllUnfinished(): array
    {
        return $this->findBy([
            'state' => Test::UNFINISHED_STATES,
        ]);
    }

    public function getFailedCount(): int
    {
        return $this->getCountByState(Test::STATE_FAILED);
    }

    public function getCancelledCount(): int
    {
        return $this->getCountByState(Test::STATE_CANCELLED);
    }

    /**
     * @return string[]
     */
    public function findAllRelativeSources(): array
    {
        $queryBuilder = $this->createQueryBuilder('Test');
        $queryBuilder
            ->select('Test.source');

        $query = $queryBuilder->getQuery();

        $result = $query->getArrayResult();

        $sources = [];
        foreach ($result as $item) {
            if (is_array($item)) {
                $sources[] = (string) ($item['source'] ?? null);
            }
        }

        return $this->sourcePathTranslator->stripCompilerSourceDirectoryFromPaths($sources);
    }

    /**
     * @param Test::STATE_* $state
     *
     * @return int
     */
    private function getCountByState(string $state): int
    {
        return count($this->findBy([
            'state' => $state,
        ]));
    }
}
