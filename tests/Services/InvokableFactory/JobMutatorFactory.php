<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class JobMutatorFactory
{
    public static function create(callable $mutator): InvokableInterface
    {
        return new Invokable(
            function (JobStore $jobStore, callable $mutator): Job {
                $job = $mutator($jobStore->getJob());

                $jobStore->store($job);

                return $job;
            },
            [
                new ServiceReference(JobStore::class),
                $mutator,
            ]
        );
    }

    /**
     * @param string[] $sources
     *
     * @return InvokableInterface
     */
    public static function createSetSources(array $sources): InvokableInterface
    {
        return self::create(function (Job $job) use ($sources): Job {
            $job->setSources($sources);

            return $job;
        });
    }

    /**
     * @param Job::STATE_* $state
     *
     * @return InvokableInterface
     */
    public static function createSetState(string $state): InvokableInterface
    {
        return self::create(function (Job $job) use ($state): Job {
            $job->setState($state);

            return $job;
        });
    }
}
