<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Model\Workflow\CompilationWorkflow;
use App\Services\CompilationWorkflowFactory;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;

class CompilationWorkflowFactoryTest extends AbstractBaseFunctionalTest
{
    private CompilationWorkflowFactory $compilationWorkflowFactory;
    private JobStore $jobStore;
    private TestStore $testStore;

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

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);
        if ($testStore instanceof TestStore) {
            $this->testStore = $testStore;
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(callable $setup, CompilationWorkflow $expectedWorkflow)
    {
        $setup($this->jobStore, $this->testStore);

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
                'setup' => function (JobStore $jobStore, TestStore $testStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $testConfiguration = TestConfiguration::create('chrome', 'http://example.com');
                    $testStore->create($testConfiguration, '/app/source/Test/testZebra.yml', '', 0);
                },
                'expectedWorkflow' => new CompilationWorkflow(
                    [
                        'Test/testZebra.yml',
                    ],
                    'Test/testApple.yml'
                ),
            ],
            'test exists for first and second sources' => [
                'setup' => function (JobStore $jobStore, TestStore $testStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $testConfiguration = TestConfiguration::create('chrome', 'http://example.com');
                    $testStore->create($testConfiguration, '/app/source/Test/testZebra.yml', '', 0);
                    $testStore->create($testConfiguration, '/app/source/Test/testApple.yml', '', 0);
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
                'setup' => function (JobStore $jobStore, TestStore $testStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $testConfiguration = TestConfiguration::create('chrome', 'http://example.com');
                    $testStore->create($testConfiguration, '/app/source/Test/testZebra.yml', '', 0);
                    $testStore->create($testConfiguration, '/app/source/Test/testApple.yml', '', 0);
                    $testStore->create($testConfiguration, '/app/source/Test/testBat.yml', '', 0);
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
