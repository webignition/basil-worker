<?php

namespace App\Repository;

use App\Entity\Callback\CallbackEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CallbackEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallbackEntity|null findOneBy(array $criteria, array $orderBy = null)
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
}
