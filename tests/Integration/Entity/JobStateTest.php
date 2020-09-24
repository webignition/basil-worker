<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\JobState;
use App\Services\YamlResourceDataProvider;
use Doctrine\ORM\EntityManagerInterface;

class JobStateTest extends AbstractEntityTest
{
    public function testJobStatesExist()
    {
        /** @var EntityManagerInterface $entityManger */
        $entityManger = self::$container->get(EntityManagerInterface::class);
        $entityRepository = $entityManger->getRepository(JobState::class);

        /** @var YamlResourceDataProvider $jobStateDataProvider */
        $jobStateDataProvider = self::$container->get('app.services.job-states-data-provider');

        $jobStateNames = $jobStateDataProvider->getData();
        $jobStateNames = $jobStateNames ?? [];

        foreach ($jobStateNames as $jobStateName) {
            $entity = $entityRepository->findOneBy([
                'name' => $jobStateName,
            ]);

            self::assertInstanceOf(JobState::class, $entity);
        }
    }
}
