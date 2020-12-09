<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\EntityPersister;

class TestMutatorFactory
{
    public static function create(Test $test, callable $mutator): InvokableInterface
    {
        return new Invokable(
            function (Test $test, EntityPersister $entityPersister, callable $mutator): Test {
                $test = $mutator($test);

                $entityPersister->persist($test);

                return $test;
            },
            [
                $test,
                new ServiceReference(EntityPersister::class),
                $mutator,
            ]
        );
    }

    /**
     * @param Test::STATE_* $state
     *
     * @return InvokableInterface
     */
    public static function createSetState(Test $test, string $state): InvokableInterface
    {
        return self::create($test, function (Test $test) use ($state): Test {
            $test->setState($state);

            return $test;
        });
    }
}
