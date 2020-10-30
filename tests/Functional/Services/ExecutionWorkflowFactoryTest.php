<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Model\Workflow\CompilationWorkflow;
use App\Model\Workflow\ExecutionWorkflow;
use App\Services\ExecutionWorkflowFactory;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;

class ExecutionWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    private const JOB_SOURCES = [
        '/tests/test1.yml',
        '/tests/test2.yml',
    ];

    private ExecutionWorkflowFactory $executionWorkflowFactory;
    private JobStore $jobStore;
    private TestStore $testStore;

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
            $this->jobStore = $jobStore;

            $job = $this->jobStore->create('label', 'http://example.com/callback');
            $job->setSources(self::JOB_SOURCES);
        }

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);
        if ($testStore instanceof TestStore) {
            $this->testStore = $testStore;
            $this->createTests();
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(callable $setup, callable $expectedWorkflowCreator)
    {
        $setup($this->jobStore, $this->testStore);

        self::assertEquals($expectedWorkflowCreator($this->testStore), $this->executionWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no tests executed' => [
                'initializer' => function () {
                },
                'expectedWorkflowCreator' => function (TestStore $testStore) {
                    $nextTest = $testStore->findNextAwaiting();
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
                'initializer' => function () {
                },
                'expectedWorkflowCreator' => function (TestStore $testStore) {
                    $nextTest = $testStore->findNextAwaiting();
                    if ($nextTest instanceof Test) {
                        $nextTest->setState(Test::STATE_COMPLETE);
                        $testStore->store($nextTest);
                    }

                    $nextTest = $testStore->findNextAwaiting();
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
                'initializer' => function () {
                },
                'expectedWorkflowCreator' => function (TestStore $testStore) {
                    $nextTest = $testStore->findNextAwaiting();
                    if ($nextTest instanceof Test) {
                        $nextTest->setState(Test::STATE_COMPLETE);
                        $testStore->store($nextTest);
                    }

                    $nextTest = $testStore->findNextAwaiting();
                    if ($nextTest instanceof Test) {
                        $nextTest->setState(Test::STATE_COMPLETE);
                        $testStore->store($nextTest);
                    }

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
        $this->testStore->create(
            TestConfiguration::create('chrome', 'http://example.com/' . $index),
            '/app/source/' . $source,
            '/generated/GeneratedTest' . $index . '.php',
            1
        );
    }
}
