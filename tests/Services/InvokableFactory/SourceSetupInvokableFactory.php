<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\SourceFactory;

class SourceSetupInvokableFactory
{
    /**
     * @param SourceSetup[] $sourceSetupCollection
     *
     * @return InvokableInterface
     */
    public static function setupCollection(array $sourceSetupCollection): InvokableInterface
    {
        $collection = [];

        foreach ($sourceSetupCollection as $sourceSetup) {
            $collection[] = self::setup($sourceSetup);
        }

        $collection[] = SourceGetterFactory::getAll();

        return new InvokableCollection($collection);
    }

    public static function setup(?SourceSetup $sourceSetup = null): InvokableInterface
    {
        $sourceSetup = $sourceSetup instanceof SourceSetup ? $sourceSetup : new SourceSetup();

        return new Invokable(
            function (SourceFactory $sourceFactory, SourceSetup $sourceSetup): Source {
                return $sourceFactory->create($sourceSetup->getType(), $sourceSetup->getPath());
            },
            [
                new ServiceReference(SourceFactory::class),
                $sourceSetup,
            ]
        );
    }
}
