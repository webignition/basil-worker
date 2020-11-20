<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Callback\CallbackInterface;
use App\Repository\CallbackRepository;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\InvokableFactory\CallbackSetup;
use App\Tests\Services\InvokableFactory\CallbackSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class CallbackRepositoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private CallbackRepository $repository;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testFind()
    {
        self::assertNull($this->repository->find(0));

        $callback = $this->invokableHandler->invoke(CallbackSetupInvokableFactory::setup());

        $retrievedCallback = $this->repository->find($callback->getId());
        self::assertEquals($callback, $retrievedCallback);
    }

    /**
     * @dataProvider getFinishedCountDataProvider
     *
     * @param array<CallbackInterface::STATE_*> $callbackStates
     * @param int $expectedFinishedCount
     */
    public function testGetFinishedCount(array $callbackStates, int $expectedFinishedCount)
    {
        foreach ($callbackStates as $callbackState) {
            $this->invokableHandler->invoke(CallbackSetupInvokableFactory::setup(
                (new CallbackSetup())
                    ->withState($callbackState)
            ));
        }

        self::assertSame($expectedFinishedCount, $this->repository->getFinishedCount());
    }

    public function getFinishedCountDataProvider(): array
    {
        return [
            'no callbacks' => [
                'callbackStates' => [],
                'expectedFinishedCount' => 0,
            ],
            'none finished' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                ],
                'expectedFinishedCount' => 0,
            ],
            'one complete' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                ],
                'expectedFinishedCount' => 1,
            ],
            'one failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedFinishedCount' => 1,
            ],
            'two complete, three failed' => [
                'callbackStates' => [
                    CallbackInterface::STATE_AWAITING,
                    CallbackInterface::STATE_QUEUED,
                    CallbackInterface::STATE_SENDING,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_COMPLETE,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                    CallbackInterface::STATE_FAILED,
                ],
                'expectedFinishedCount' => 5,
            ],
        ];
    }

    /**
     * @dataProvider getCompileFailureTypeCountDataProvider
     *
     * @param array<CallbackInterface::TYPE_*> $callbackTypes
     * @param int $expectedCompileFailureTypeCount
     */
    public function testGetCompileFailureTypeCount(array $callbackTypes, int $expectedCompileFailureTypeCount)
    {
        foreach ($callbackTypes as $callbackType) {
            $this->invokableHandler->invoke(CallbackSetupInvokableFactory::setup(
                (new CallbackSetup())
                    ->withType($callbackType)
            ));
        }

        self::assertSame($expectedCompileFailureTypeCount, $this->repository->getCompileFailureTypeCount());
    }

    public function getCompileFailureTypeCountDataProvider(): array
    {
        return [
            'no callbacks' => [
                'callbackTypes' => [],
                'expectedCompileFailureTypeCount' => 0,
            ],
            'no compile-failure' => [
                'callbackTypes' => [
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                ],
                'expectedCompileFailureTypeCount' => 0,
            ],
            'one compile-failure' => [
                'callbackTypes' => [
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                ],
                'expectedCompileFailureTypeCount' => 1,
            ],
            'two compile-failure' => [
                'callbackTypes' => [
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                ],
                'expectedCompileFailureTypeCount' => 2,
            ],
            'five compile-failure' => [
                'callbackTypes' => [
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_EXECUTE_DOCUMENT_RECEIVED,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                    CallbackInterface::TYPE_COMPILE_FAILURE,
                ],
                'expectedCompileFailureTypeCount' => 5,
            ],
        ];
    }
}
