<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Services\JobSourceFinder;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\TestTestFactory;

class JobSourceFinderTest extends AbstractBaseFunctionalTest
{
    private JobSourceFinder $jobSourceFinder;
    private JobStore $jobStore;
    private TestTestFactory $testFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $jobSourceFinder = self::$container->get(JobSourceFinder::class);
        self::assertInstanceOf(JobSourceFinder::class, $jobSourceFinder);
        if ($jobSourceFinder instanceof JobSourceFinder) {
            $this->jobSourceFinder = $jobSourceFinder;
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
     * @dataProvider findNextNonCompiledSourceDataProvider
     */
    public function testFindNextNonCompiledSource(callable $setup, ?string $expectedNextNonCompiledSource)
    {
        $setup($this->jobStore, $this->testFactory);
        self::assertSame($expectedNextNonCompiledSource, $this->jobSourceFinder->findNextNonCompiledSource());
    }

    public function findNextNonCompiledSourceDataProvider(): array
    {
        return [
            'no job' => [
                'setup' => function () {
                },
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, no sources' => [
                'setup' => function (JobStore $jobStore) {
                    $jobStore->create('label', 'http://example.com/callback');
                },
                'expectedNextNonCompiledSource' => null,
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
                'expectedNextNonCompiledSource' => 'Test/testZebra.yml',
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
                'expectedNextNonCompiledSource' => 'Test/testApple.yml',
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
                'expectedNextNonCompiledSource' => 'Test/testBat.yml',
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
                'expectedNextNonCompiledSource' => null,
            ],
        ];
    }

    /**
     * @dataProvider findCompiledSourcesDataProvider
     *
     * @param callable $setup
     * @param string[] $expectedCompiledSources
     */
    public function testFindCompiledSources(callable $setup, array $expectedCompiledSources)
    {
        $setup($this->jobStore, $this->testFactory);

        self::assertSame($expectedCompiledSources, $this->jobSourceFinder->findCompiledSources());
    }

    public function findCompiledSourcesDataProvider(): array
    {
        return [
            'no job' => [
                'setup' => function () {
                },
                'expectedCompiledSources' => [],
            ],
            'has job, no sources' => [
                'setup' => function (JobStore $jobStore) {
                    $jobStore->create('label', 'http://example.com/callback');
                },
                'expectedCompiledSources' => [],
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
                'expectedCompiledSources' => [],
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
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                ],
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
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                    'Test/testApple.yml',
                ],
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
                'expectedCompiledSources' => [
                    'Test/testZebra.yml',
                    'Test/testApple.yml',
                    'Test/testBat.yml',
                ],
            ],
        ];
    }
}
