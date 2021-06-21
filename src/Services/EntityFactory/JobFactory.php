<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use webignition\BasilWorker\PersistenceBundle\Entity\Job;

class JobFactory extends AbstractEntityFactory
{
    public function create(string $label, string $callbackUrl, int $maximumDurationInSeconds): Job
    {
        $job = Job::create($label, $callbackUrl, $maximumDurationInSeconds);

        $this->persist($job);

        return $job;
    }
}
