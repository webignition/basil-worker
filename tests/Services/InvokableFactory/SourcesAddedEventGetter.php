<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\SourcesAddedEventSubscriber;
use Symfony\Contracts\EventDispatcher\Event;

class SourcesAddedEventGetter
{
    public static function get(): InvokableInterface
    {
        return new Invokable(
            function (SourcesAddedEventSubscriber $sourcesAddedEventSubscriber): ?Event {
                return $sourcesAddedEventSubscriber->getEvent();
            },
            [
                new ServiceReference(SourcesAddedEventSubscriber::class),
            ]
        );
    }
}
