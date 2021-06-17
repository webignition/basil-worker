<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRemover;

abstract class AbstractBaseIntegrationTest extends AbstractBaseFunctionalTest
{
    protected EntityRemover $entityRemover;

    protected function setUp(): void
    {
        parent::setUp();

        $entityRemover = self::$container->get(EntityRemover::class);
        \assert($entityRemover instanceof EntityRemover);
        $this->entityRemover = $entityRemover;
    }
}
