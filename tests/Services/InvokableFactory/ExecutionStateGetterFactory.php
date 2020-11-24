<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\ExecutionState;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class ExecutionStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (ExecutionState $executionState): string {
                return $executionState->getCurrentState();
            },
            [
                new ServiceReference(ExecutionState::class),
            ]
        );
    }
}
