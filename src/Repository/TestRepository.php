<?php

namespace App\Repository;

use App\Entity\Test;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Test::class);
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

    public function getAwaitingCount(): int
    {
        return count($this->findBy([
            'state' => Test::STATE_AWAITING,
        ]));
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
}
