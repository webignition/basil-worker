<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\ExecutionStateFactory;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class ExecutionStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (ExecutionStateFactory $executionStateFactory): string {
                return $executionStateFactory->getCurrentState();
            },
            [
                new ServiceReference(ExecutionStateFactory::class),
            ]
        );
    }
}
