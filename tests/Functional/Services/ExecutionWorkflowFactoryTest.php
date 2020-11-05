<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Model\Workflow\CompilationWorkflow;
use App\Model\Workflow\ExecutionWorkflow;
use App\Repository\TestRepository;
use App\Services\ExecutionWorkflowFactory;
use App\Services\JobStore;
use App\Services\TestStateMutator;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;

class ExecutionWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    private const JOB_SOURCES = [
        '/tests/test1.yml',
        '/tests/test2.yml',
    ];

    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private TestTestFactory $testFactory;
    private TestRepository $testRepository;
    private TestStateMutator $testStateMutator;

    protected function setUp(): void
    {
        parent::setUp();

        $executionWorkflowFactory = self::$container->get(ExecutionWorkflowFactory::class);
        self::assertInstanceOf(ExecutionWorkflowFactory::class, $executionWorkflowFactory);
        if ($executionWorkflowFactory instanceof ExecutionWorkflowFactory) {
            $this->executionWorkflowFactory = $executionWorkflowFactory;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $job = $jobStore->create('label', 'http://example.com/callback');
            $job->setSources(self::JOB_SOURCES);
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
            $this->createTests();
        }

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }

        $testStateMutator = self::$container->get(TestStateMutator::class);
        self::assertInstanceOf(TestStateMutator::class, $testStateMutator);
        if ($testStateMutator instanceof TestStateMutator) {
            $this->testStateMutator = $testStateMutator;
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(callable $setup, callable $expectedWorkflowCreator)
    {
        $setup($this->testRepository, $this->testStateMutator);

        $workflow = $expectedWorkflowCreator($this->testRepository);

        self::assertEquals($workflow, $this->executionWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no tests executed' => [
                'setup' => function () {
                },
                'expectedWorkflowCreator' => function (TestRepository $testRepository) {
                    $nextTest = $testRepository->findNextAwaiting();
                    $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                    return new ExecutionWorkflow(
                        CompilationWorkflow::STATE_COMPLETE,
                        2,
                        2,
                        $nextTestId
                    );
                },
            ],
            'first test executed' => [
                'setup' => function (TestRepository $testRepository, TestStateMutator $testStateMutator) {
                    $nextTest = $testRepository->findNextAwaiting();
                    if ($nextTest instanceof Test) {
                        $testStateMutator->setComplete($nextTest);
                    }
                },
                'expectedWorkflowCreator' => function (TestRepository $testRepository) {
                    $nextTest = $testRepository->findNextAwaiting();
                    $nextTestId = $nextTest instanceof Test ? $nextTest->getId() : null;

                    return new ExecutionWorkflow(
                        CompilationWorkflow::STATE_COMPLETE,
                        2,
                        1,
                        $nextTestId
                    );
                },
            ],
            'both tests executed' => [
                'setup' => function (TestRepository $testRepository, TestStateMutator $testStateMutator) {
                    $nextTest = $testRepository->findNextAwaiting();
                    if ($nextTest instanceof Test) {
                        $testStateMutator->setComplete($nextTest);
                    }

                    $nextTest = $testRepository->findNextAwaiting();
                    if ($nextTest instanceof Test) {
                        $testStateMutator->setComplete($nextTest);
                    }
                },
                'expectedWorkflowCreator' => function () {
                    return new ExecutionWorkflow(
                        CompilationWorkflow::STATE_COMPLETE,
                        2,
                        0,
                        null
                    );
                },
            ],
        ];
    }

    private function createTests(): void
    {
        foreach (self::JOB_SOURCES as $sourceIndex => $source) {
            $this->createTest($source, $sourceIndex);
        }
    }

    private function createTest(string $source, int $index): void
    {
        $this->testFactory->create(
            TestConfiguration::create('chrome', 'http://example.com/' . $index),
            '/app/source/' . $source,
            '/generated/GeneratedTest' . $index . '.php',
            1
        );
    }
}
