<?php

namespace App\Repository;

use App\Entity\CallbackEntity;
use App\Entity\CallbackEntityInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CallbackEntityInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallbackEntityInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method CallbackEntityInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<CallbackEntityInterface>
 */
class CallbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallbackEntity::class);
    }
}
