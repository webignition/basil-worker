<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Repository\TestRepository;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class TestGetterFactory
{
    public static function getAll(): InvokableInterface
    {
        return new Invokable(
            function (TestRepository $testRepository): array {
                return $testRepository->findAll();
            },
            [
                new ServiceReference(TestRepository::class),
            ]
        );
    }
}
