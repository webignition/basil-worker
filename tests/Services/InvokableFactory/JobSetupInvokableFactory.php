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
    /**
     * @param string $label
     * @param string $callbackUrl
     * @param string[] $sources
     *
     * @return InvokableInterface
     */
    public static function createJobWithSources(string $label, string $callbackUrl, array $sources): InvokableInterface
    {
        return new InvokableCollection([
            self::createJob($label, $callbackUrl),
            self::setJobSources($sources),
        ]);
    }

    public static function createJob(string $label, string $callbackUrl): InvokableInterface
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
     * @param string[]  $sources
     * @return InvokableInterface
     */
    public static function setJobSources(array $sources): InvokableInterface
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
