<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\EntityFactory;

use App\Services\EntityFactory\JobFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Entity\Job;

class JobFactoryTest extends AbstractBaseFunctionalTest
{
    private JobFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(JobFactory::class);
        \assert($factory instanceof JobFactory);
        $this->factory = $factory;
    }

    public function testCreate(): void
    {
        $label = 'label content';
        $callbackUrl = 'http://example.com';
        $maximumDurationInSeconds = 600;

        $job = $this->factory->create($label, $callbackUrl, $maximumDurationInSeconds);

        self::assertSame(Job::ID, $job->getId());
        self::assertSame($label, $job->getLabel());
        self::assertSame($callbackUrl, $job->getCallbackUrl());
        self::assertSame($maximumDurationInSeconds, $job->getMaximumDurationInSeconds());
    }
}
