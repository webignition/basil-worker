<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Model\CompilationState;
use App\Services\CompilationStateFactory;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class CompilationStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (CompilationStateFactory $compilationStateFactory): CompilationState {
                return $compilationStateFactory->create();
            },
            [
                new ServiceReference(CompilationStateFactory::class),
            ]
        );
    }
}
