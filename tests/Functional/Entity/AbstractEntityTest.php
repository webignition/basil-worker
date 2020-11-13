<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\TestClassServicePropertyInjectorTrait;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractEntityTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }
}
