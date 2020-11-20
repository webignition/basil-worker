<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class JobSetupInvokableFactory
{
    public static function setup(JobSetup $jobSetup): InvokableInterface
    {
        $collection = [];

        $collection[] = self::create($jobSetup->getLabel(), $jobSetup->getCallbackUrl());
        $collection[] = self::setState($jobSetup->getState());

        $sources = $jobSetup->getSources();
        if (is_array($sources)) {
            $collection[] = self::setSources($sources);
        }

        return new InvokableCollection($collection);
    }

    private static function create(string $label, string $callbackUrl): InvokableInterface
    {
        return new Invokable(
            function (JobStore $jobStore, string $label, string $callbackUrl): Job {
                return $jobStore->create($label, $callbackUrl);
            },
            [
                new ServiceReference(JobStore::class),
                $label,
                $callbackUrl,
            ]
        );
    }

    /**
     * @param Job::STATE_* $state
     *
     * @return InvokableInterface
     */
    private static function setState(string $state): InvokableInterface
    {
        return new Invokable(
            function (JobStore $jobStore, string $state): ?Job {
                if (
                    Job::STATE_COMPILATION_AWAITING !== $state &&
                    Job::STATE_COMPILATION_RUNNING !== $state &&
                    Job::STATE_COMPILATION_FAILED !== $state &&
                    Job::STATE_EXECUTION_AWAITING !== $state &&
                    Job::STATE_EXECUTION_RUNNING !== $state &&
                    Job::STATE_EXECUTION_FAILED !== $state &&
                    Job::STATE_EXECUTION_COMPLETE !== $state &&
                    Job::STATE_EXECUTION_CANCELLED !== $state
                ) {
                    $state = Job::STATE_COMPILATION_AWAITING;
                }


                if ($jobStore->hasJob()) {
                    $job = $jobStore->getJob();
                    $job->setState($state);

                    $jobStore->store($job);

                    return $job;
                }

                return null;
            },
            [
                new ServiceReference(JobStore::class),
                $state
            ]
        );
    }

    /**
     * @param string[]  $sources
     * @return InvokableInterface
     */
    private static function setSources(array $sources): InvokableInterface
    {
        return new Invokable(
            function (JobStore $jobStore, array $sources): ?Job {
                if ($jobStore->hasJob()) {
                    $job = $jobStore->getJob();
                    $job->setSources($sources);

                    $jobStore->store($job);

                    return $job;
                }

                return null;
            },
            [
                new ServiceReference(JobStore::class),
                $sources
            ]
        );
    }
}
