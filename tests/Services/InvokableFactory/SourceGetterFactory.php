<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;

class SourceGetterFactory
{
    public static function getAll(): InvokableInterface
    {
        return new Invokable(
            function (EntityManagerInterface $entityManager): array {
                $sourceRepository = $entityManager->getRepository(Source::class);

                return $sourceRepository->findAll();
            },
            [
                new ServiceReference(EntityManagerInterface::class),
            ]
        );
    }

    public static function getAllRelativePaths(): InvokableInterface
    {
        return new Invokable(
            function (SourceStore $sourceStore): array {
                return $sourceStore->findAllPaths();
            },
            [
                new ServiceReference(SourceStore::class),
            ]
        );
    }
}
