<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class JobGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (JobStore $jobStore): ?Job {
                if ($jobStore->hasJob()) {
                    return $jobStore->getJob();
                }

                return null;
            },
            [
                new ServiceReference(JobStore::class),
            ]
        );
    }
}
