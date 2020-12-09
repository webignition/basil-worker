<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

class JobGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (JobStore $jobStore): ?Job {
                if ($jobStore->has()) {
                    return $jobStore->get();
                }

                return null;
            },
            [
                new ServiceReference(JobStore::class),
            ]
        );
    }
}
