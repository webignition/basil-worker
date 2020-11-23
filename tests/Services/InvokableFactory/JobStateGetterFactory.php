<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Model\JobState;
use App\Services\JobStateFactory;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class JobStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (JobStateFactory $jobStateFactory): JobState {
                return $jobStateFactory->create();
            },
            [
                new ServiceReference(JobStateFactory::class),
            ]
        );
    }
}
