<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Services\ApplicationState;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class ApplicationStateGetterFactory
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (ApplicationState $applicationState): string {
                return $applicationState->getCurrentState();
            },
            [
                new ServiceReference(ApplicationState::class),
            ]
        );
    }
}
