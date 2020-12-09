<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;

class JobSetupInvokableFactory
{
    public static function setup(?JobSetup $jobSetup = null): InvokableInterface
    {
        $jobSetup = $jobSetup instanceof JobSetup ? $jobSetup : new JobSetup();

        $collection = [];

        $collection[] = self::create(
            $jobSetup->getLabel(),
            $jobSetup->getCallbackUrl(),
            $jobSetup->getMaximumDurationInSeconds()
        );

        return new InvokableCollection($collection);
    }

    private static function create(
        string $label,
        string $callbackUrl,
        int $maximumDurationInSeconds
    ): InvokableInterface {
        return new Invokable(
            function (JobFactory $jobFactory, string $label, string $callbackUrl, int $maximumDurationInSeconds): Job {
                return $jobFactory->create($label, $callbackUrl, $maximumDurationInSeconds);
            },
            [
                new ServiceReference(JobFactory::class),
                $label,
                $callbackUrl,
                $maximumDurationInSeconds,
            ]
        );
    }
}
