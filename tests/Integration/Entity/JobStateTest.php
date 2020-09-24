<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\JobState;
use App\Services\JobStateRetriever;
use App\Services\YamlResourceDataProvider;

class JobStateTest extends AbstractEntityTest
{
    public function testJobStatesExist()
    {
        /** @var JobStateRetriever $jobStateRetriever */
        $jobStateRetriever = self::$container->get(JobStateRetriever::class);

        /** @var YamlResourceDataProvider $jobStateDataProvider */
        $jobStateDataProvider = self::$container->get('app.services.job-states-data-provider');

        $jobStateNames = $jobStateDataProvider->getData();
        $jobStateNames = $jobStateNames ?? [];

        foreach ($jobStateNames as $jobStateName) {
            $entity = $jobStateRetriever->retrieve($jobStateName);

            self::assertInstanceOf(JobState::class, $entity);
        }
    }
}
