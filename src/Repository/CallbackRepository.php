<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Callback\CallbackEntity;

/**
 * @method CallbackEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallbackEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method CallbackEntity[]    findAll()
 * @method CallbackEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<CallbackEntity>
 */
class CallbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallbackEntity::class);
    }

    public function hasForType(string $type): bool
    {
        return $this->count(['type' => $type]) > 0;
    }
}
