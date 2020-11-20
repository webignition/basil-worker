<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Model\Workflow\ExecutionWorkflow;
use App\Model\Workflow\WorkflowInterface;
use App\Repository\TestRepository;
use App\Services\ExecutionWorkflowFactory;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class ExecutionWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private const JOB_SOURCES = [
        '/tests/test1.yml',
        '/tests/test2.yml',
    ];

    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $this->invokableHandler->invoke(JobSetupInvokableFactory::setup(
            (new JobSetup())->withSources(self::JOB_SOURCES)
        ));

        foreach (self::JOB_SOURCES as $source) {
            $this->invokableHandler->invoke(TestSetupInvokableFactory::setupCollection([
                (new TestSetup())
                    ->withSource('/app/source/' . $source)
            ]));
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(InvokableInterface $setup, InvokableInterface $expectedWorkflowCreator)
    {
        $this->invokableHandler->invoke($setup);

        $workflow = $this->invokableHandler->invoke($expectedWorkflowCreator);

        self::assertEquals($workflow, $this->executionWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no tests executed' => [
                'setup' => Invokable::createEmpty(),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(
                            WorkflowInterface::STATE_COMPLETE,
                            2,
                            2,
                            $nextTestId
                        );
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'first test executed' => [
                'setup' => new Invokable(
                    function (TestRepository $testRepository, TestStateMutator $testStateMutator) {
                        $nextTest = $testRepository->findNextAwaiting();
                        if ($nextTest instanceof Test) {
                            $testStateMutator->setRunning($nextTest);
                            $testStateMutator->setComplete($nextTest);
                        }
                    },
                    [
                        new ServiceReference(TestRepository::class),
                        new ServiceReference(TestStateMutator::class),
                    ]
                ),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(
                            WorkflowInterface::STATE_COMPLETE,
                            2,
                            1,
                            $nextTestId
                        );
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'both tests executed' => [
                'setup' => new Invokable(
                    function (TestRepository $testRepository, TestStateMutator $testStateMutator) {
                        $nextTest = $testRepository->findNextAwaiting();
                        if ($nextTest instanceof Test) {
                            $testStateMutator->setRunning($nextTest);
                            $testStateMutator->setComplete($nextTest);
                        }

                        $nextTest = $testRepository->findNextAwaiting();
                        if ($nextTest instanceof Test) {
                            $testStateMutator->setRunning($nextTest);
                            $testStateMutator->setComplete($nextTest);
                        }
                    },
                    [
                        new ServiceReference(TestRepository::class),
                        new ServiceReference(TestStateMutator::class),
                    ]
                ),
                'expectedWorkflowCreator' => new Invokable(
                    function () {
                        return new ExecutionWorkflow(
                            WorkflowInterface::STATE_COMPLETE,
                            2,
                            0,
                            null
                        );
                    }
                ),
            ],
        ];
    }
}
