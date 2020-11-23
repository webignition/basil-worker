<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Model\Workflow\ExecutionWorkflow;
use App\Repository\TestRepository;
use App\Services\ExecutionWorkflowFactory;
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
        '/tests/test3.yml',
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

//        foreach (self::JOB_SOURCES as $source) {
//            $this->invokableHandler->invoke(TestSetupInvokableFactory::setupCollection([
//                (new TestSetup())
//                    ->withSource('/app/source/' . $source)
//            ]));
//        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(InvokableInterface $setup, InvokableInterface $expectedWorkflowCreator)
    {
        $this->invokableHandler->invoke($setup);

        $expectedWorkflow = $this->invokableHandler->invoke($expectedWorkflowCreator);

//        $tests = $this->invokableHandler->invoke(TestGetterFactory::getAll());
//        var_dump($tests);
//        exit();

        self::assertEquals($expectedWorkflow, $this->executionWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no tests' => [
                'setup' => Invokable::createEmpty(),
                'expectedWorkflowCreator' => new Invokable(
                    function () {
                        return new ExecutionWorkflow(0, 0, 0, null);
                    }
                ),
            ],
            'no finished tests, no running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(0, 0, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'no finished tests, has running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_RUNNING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(0, 1, 0, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'no finished tests, has running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_RUNNING),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(0, 1, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (complete), no running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_COMPLETE),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 0, 0, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (cancelled), no running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_CANCELLED),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 0, 0, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (failed), no running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_FAILED),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 0, 0, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (complete), no running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_COMPLETE),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 0, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (cancelled), no running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_CANCELLED),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 0, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (failed), no running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_FAILED),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 0, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (complete), has running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_COMPLETE),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_RUNNING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function () {
                        return new ExecutionWorkflow(1, 1, 0, null);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (cancelled), has running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_CANCELLED),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_RUNNING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function () {
                        return new ExecutionWorkflow(1, 1, 0, null);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (failed), has running tests, no awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_FAILED),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_RUNNING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function () {
                        return new ExecutionWorkflow(1, 1, 0, null);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (complete), has running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_COMPLETE),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_RUNNING),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[2])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 1, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (cancelled), has running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_CANCELLED),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_RUNNING),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[2])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 1, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
            'has finished tests (failed), has running tests, has awaiting tests' => [
                'setup' => TestSetupInvokableFactory::setupCollection([
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[0])
                        ->withState(Test::STATE_FAILED),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[1])
                        ->withState(Test::STATE_RUNNING),
                    (new TestSetup())
                        ->withSource('/app/source/' . self::JOB_SOURCES[2])
                        ->withState(Test::STATE_AWAITING),
                ]),
                'expectedWorkflowCreator' => new Invokable(
                    function (TestRepository $testRepository) {
                        $nextTest = $testRepository->findNextAwaiting();
                        $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                        return new ExecutionWorkflow(1, 1, 1, $nextTestId);
                    },
                    [
                        new ServiceReference(TestRepository::class),
                    ]
                ),
            ],
        ];
    }
}
