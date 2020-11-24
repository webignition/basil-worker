<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\CompilationState;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class CompilationStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (CompilationState $compilationState): string {
                return $compilationState->getCurrentState();
            },
            [
                new ServiceReference(CompilationState::class),
            ]
        );
    }
}
