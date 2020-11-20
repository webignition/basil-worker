<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\TestTestFactory;

class TestSetupInvokableFactory
{
    /**
     * @param TestSetup[] $testSetupCollection
     *
     * @return InvokableInterface
     */
    public static function setupCollection(array $testSetupCollection): InvokableInterface
    {
        $collection = [];

        foreach ($testSetupCollection as $testSetup) {
            $collection[] = self::setup($testSetup);
        }

        $collection[] = TestGetterFactory::getAll();

        return new InvokableCollection($collection);
    }

    public static function setup(TestSetup $testSetup): InvokableInterface
    {
        return new Invokable(
            function (TestTestFactory $testFactory, TestSetup $testSetup) {
                return $testFactory->create(
                    $testSetup->getConfiguration(),
                    $testSetup->getSource(),
                    $testSetup->getTarget(),
                    $testSetup->getStepCount(),
                    $testSetup->getState()
                );
            },
            [
                new ServiceReference(TestTestFactory::class),
                $testSetup,
            ]
        );
    }
}
