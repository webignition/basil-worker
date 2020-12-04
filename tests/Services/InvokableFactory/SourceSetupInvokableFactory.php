<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Source;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use Doctrine\ORM\EntityManagerInterface;

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
            function (EntityManagerInterface $entityManager, SourceSetup $sourceSetup): Source {
                $source = Source::create($sourceSetup->getType(), $sourceSetup->getPath());

                $entityManager->persist($source);
                $entityManager->flush();

                return $source;
            },
            [
                new ServiceReference(EntityManagerInterface::class),
                $sourceSetup,
            ]
        );
    }
}
