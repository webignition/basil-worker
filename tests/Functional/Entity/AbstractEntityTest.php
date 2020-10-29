<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractEntityTest extends AbstractBaseFunctionalTest
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }
    }
}
