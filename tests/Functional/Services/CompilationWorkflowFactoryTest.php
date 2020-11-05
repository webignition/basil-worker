<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Model\Workflow\CompilationWorkflow;
use App\Services\CompilationWorkflowFactory;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;

class CompilationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private JobStore $jobStore;
    private TestTestFactory $testFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $compilationWorkflowFactory = self::$container->get(CompilationWorkflowFactory::class);
        self::assertInstanceOf(CompilationWorkflowFactory::class, $compilationWorkflowFactory);
        if ($compilationWorkflowFactory instanceof CompilationWorkflowFactory) {
            $this->compilationWorkflowFactory = $compilationWorkflowFactory;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);
        if ($testFactory instanceof TestTestFactory) {
            $this->testFactory = $testFactory;
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(callable $setup, CompilationWorkflow $expectedWorkflow)
    {
        $setup($this->jobStore, $this->testFactory);

        self::assertEquals($expectedWorkflow, $this->compilationWorkflowFactory->create());
    }

    public function createDataProvider(): array
    {
        return [
            'no job' => [
                'setup' => function () {
                },
                'expectedWorkflow' => new CompilationWorkflow([], null),
            ],
            'has job, no sources' => [
                'setup' => function (JobStore $jobStore) {
                    $jobStore->create('label', 'http://example.com/callback');
                },
                'expectedWorkflow' => new CompilationWorkflow([], null),
            ],
            'has job, has sources, no tests' => [
                'setup' => function (JobStore $jobStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);
                },
                'expectedWorkflow' => new CompilationWorkflow([], 'Test/testZebra.yml'),
            ],
            'test exists for first source' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $testConfiguration = TestConfiguration::create('chrome', 'http://example.com');
                    $testFactory->create($testConfiguration, '/app/source/Test/testZebra.yml', '', 0);
                },
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        'Test/testZebra.yml',
                    ],
                    'Test/testApple.yml'
                ),
            ],
            'test exists for first and second sources' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $testConfiguration = TestConfiguration::create('chrome', 'http://example.com');
                    $testFactory->create($testConfiguration, '/app/source/Test/testZebra.yml', '', 0);
                    $testFactory->create($testConfiguration, '/app/source/Test/testApple.yml', '', 0);
                },
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                    ],
                    'Test/testBat.yml'
                ),
            ],
            'tests exist for all sources' => [
                'setup' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $testConfiguration = TestConfiguration::create('chrome', 'http://example.com');
                    $testFactory->create($testConfiguration, '/app/source/Test/testZebra.yml', '', 0);
                    $testFactory->create($testConfiguration, '/app/source/Test/testApple.yml', '', 0);
                    $testFactory->create($testConfiguration, '/app/source/Test/testBat.yml', '', 0);
                },
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',

                    ],
                    null
                ),
            ],
        ];
    }
}
