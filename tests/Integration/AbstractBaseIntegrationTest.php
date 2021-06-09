<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRemover;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractBaseIntegrationTest extends AbstractBaseFunctionalTest
{
    protected EntityManagerInterface $entityManager;
    protected EntityRemover $entityRemover;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $entityRemover = self::$container->get(EntityRemover::class);
        \assert($entityRemover instanceof EntityRemover);
        $this->entityRemover = $entityRemover;

        $this->entityRemover->removeAll();
    }

    protected function tearDown(): void
    {
        $this->entityRemover->removeAll();

        parent::tearDown();
    }
}
