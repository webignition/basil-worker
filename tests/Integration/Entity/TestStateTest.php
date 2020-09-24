<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\TestState;
use App\Services\YamlResourceDataProvider;
use Doctrine\ORM\EntityManagerInterface;

class TestStateTest extends AbstractEntityTest
{
    public function testTestStatesExist()
    {
        /** @var EntityManagerInterface $entityManger */
        $entityManger = self::$container->get(EntityManagerInterface::class);
        $entityRepository = $entityManger->getRepository(TestState::class);

        /** @var YamlResourceDataProvider $testStateDataProvider */
        $testStateDataProvider = self::$container->get('app.services.test-states-data-provider');

        $jobStateNames = $testStateDataProvider->getData();
        $jobStateNames = $jobStateNames ?? [];

        foreach ($jobStateNames as $jobStateName) {
            $entity = $entityRepository->findOneBy([
                'name' => $jobStateName,
            ]);

            self::assertInstanceOf(TestState::class, $entity);
        }
    }
}
