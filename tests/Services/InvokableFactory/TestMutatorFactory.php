<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Test;
use App\Services\TestStore;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class TestMutatorFactory
{
    public static function create(Test $test, callable $mutator): InvokableInterface
    {
        return new Invokable(
            function (Test $test, TestStore $testStore, callable $mutator): Test {
                $test = $mutator($test);

                return $testStore->store($test);
            },
            [
                $test,
                new ServiceReference(TestStore::class),
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
