<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Repository\SourceRepository;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class SourceGetterFactory
{
    public static function getAll(): InvokableInterface
    {
        return new Invokable(
            function (SourceRepository $sourceRepository): array {
                return $sourceRepository->findAll();
            },
            [
                new ServiceReference(SourceRepository::class),
            ]
        );
    }

    public static function getAllRelativePaths(): InvokableInterface
    {
        return new Invokable(
            function (SourceRepository $sourceRepository): array {
                return $sourceRepository->findAllRelativePaths();
            },
            [
                new ServiceReference(SourceRepository::class),
            ]
        );
    }
}
