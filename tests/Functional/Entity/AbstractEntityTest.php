<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

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
