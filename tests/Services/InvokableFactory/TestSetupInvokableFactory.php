<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use webignition\BasilModels\Test\Configuration;
use App\Services\TestFactory;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilCompilerModels\TestManifest;

class TestSetupInvokableFactory
{
    /**
     * @param TestSetup[] $testSetupCollection
     *
     * @return InvokableInterface
     */
    public static function setup(array $testSetupCollection): InvokableInterface
    {
        $collection = [];

        foreach ($testSetupCollection as $testSetup) {
            $collection[] = self::create(
                $testSetup->getConfiguration(),
                $testSetup->getSource(),
                $testSetup->getTarget(),
                $testSetup->getStepCount()
            );
        }

        return new InvokableCollection($collection);
    }

    private static function create(
        Configuration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): InvokableInterface {
        return new Invokable(
            function (
                TestFactory $testFactory,
                Configuration $configuration,
                string $source,
                string $target,
                int $stepCount
            ): array {
                return $testFactory->createFromManifestCollection([
                    new TestManifest($configuration, $source, $target, $stepCount),
                ]);
            },
            [
                new ServiceReference(TestFactory::class),
                $configuration,
                $source,
                $target,
                $stepCount
            ]
        );
    }
}
