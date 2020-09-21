<?php

namespace App\Tests\Functional\Entity;

use App\Tests\Functional\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractEntityTest extends AbstractBaseFunctionalTest
{
    /**
     * @var EntityManagerInterface|null
     */
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }
    }
}
