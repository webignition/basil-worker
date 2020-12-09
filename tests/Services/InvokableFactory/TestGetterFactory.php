<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;

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

    public static function getStates(): InvokableInterface
    {
        return new Invokable(
            function (array $tests): array {
                $states = [];
                foreach ($tests as $test) {
                    $states[] = $test->getState();
                }

                return $states;
            },
            [
                TestGetterFactory::getAll(),
            ]
        );
    }

    /**
     * @param array<Test::STATE_*> $expectedStates
     *
     * @return InvokableInterface
     */
    public static function assertStates(array $expectedStates): InvokableInterface
    {
        return new Invokable(
            function (array $tests, array $expectedStates): void {
                $states = [];
                foreach ($tests as $test) {
                    $states[] = $test->getState();
                }

                TestCase::assertSame($expectedStates, $states);
            },
            [
                TestGetterFactory::getAll(),
                $expectedStates,
            ]
        );
    }
}
