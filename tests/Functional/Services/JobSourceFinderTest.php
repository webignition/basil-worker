<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\JobSourceFinder;
use App\Services\JobStore;
use App\Services\ManifestStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;
use webignition\ObjectReflector\ObjectReflector;

class JobSourceFinderTest extends AbstractBaseFunctionalTest
{
    private JobSourceFinder $jobSourceFinder;
    private JobStore $jobStore;
    private TestStore $testStore;
    private ManifestStore $manifestStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobSourceFinder = self::$container->get(JobSourceFinder::class);
        self::assertInstanceOf(JobSourceFinder::class, $jobSourceFinder);

        if ($jobSourceFinder instanceof JobSourceFinder) {
            $this->jobSourceFinder = $jobSourceFinder;

            $jobStore = ObjectReflector::getProperty($jobSourceFinder, 'jobStore');
            if ($jobStore instanceof JobStore) {
                $this->jobStore = $jobStore;
            }

            $testStore = ObjectReflector::getProperty($jobSourceFinder, 'testStore');
            if ($testStore instanceof TestStore) {
                $this->testStore = $testStore;
            }
        }

        $manifestStore = self::$container->get(ManifestStore::class);
        if ($manifestStore instanceof ManifestStore) {
            $this->manifestStore = $manifestStore;
        }
    }

    /**
     * @dataProvider findNextNonCompiledSourceDataProvider
     */
    public function testFindNextNonCompiledSource(callable $initializer, ?string $expectedNextNonCompiledSource)
    {
        $initializer($this->jobStore, $this->testStore, $this->manifestStore);
        self::assertSame($expectedNextNonCompiledSource, $this->jobSourceFinder->findNextNonCompiledSource());
    }

    public function findNextNonCompiledSourceDataProvider(): array
    {
        return [
            'no job' => [
                'initializer' => function () {
                },
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, no sources' => [
                'initializer' => function (JobStore $jobStore) {
                    $jobStore->create('label', 'http://example.com/callback');
                },
                'expectedNextNonCompiledSource' => null,
            ],
            'has job, has sources, no tests' => [
                'initializer' => function (JobStore $jobStore) {
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
                'initializer' => function (JobStore $jobStore, TestStore $testStore, ManifestStore $manifestStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $manifest = new TestManifest(
                        new Configuration('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        'generated/GeneratedTest1.php',
                        3
                    );

                    $manifestPath = $manifestStore->store($manifest);

                    $testStore->create('Test/testZebra.yml', $manifest, $manifestPath);
                },
                'expectedNextNonCompiledSource' => 'Test/testApple.yml',
            ],
            'test exists for first and second sources' => [
                'initializer' => function (JobStore $jobStore, TestStore $testStore, ManifestStore $manifestStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $manifest = new TestManifest(
                        new Configuration('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        'generated/GeneratedTest1.php',
                        3
                    );

                    $manifestPath = $manifestStore->store($manifest);

                    $testStore->create('Test/testZebra.yml', $manifest, $manifestPath);
                    $testStore->create('Test/testApple.yml', $manifest, $manifestPath);
                },
                'expectedNextNonCompiledSource' => 'Test/testBat.yml',
            ],
            'tests exist for all sources' => [
                'initializer' => function (JobStore $jobStore, TestStore $testStore, ManifestStore $manifestStore) {
                    $job = $jobStore->create('label', 'http://example.com/callback');
                    $job->setSources([
                        'Test/testZebra.yml',
                        'Test/testApple.yml',
                        'Test/testBat.yml',
                    ]);

                    $manifest = new TestManifest(
                        new Configuration('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        'generated/GeneratedTest1.php',
                        3
                    );

                    $manifestPath = $manifestStore->store($manifest);

                    $testStore->create('Test/testZebra.yml', $manifest, $manifestPath);
                    $testStore->create('Test/testApple.yml', $manifest, $manifestPath);
                    $testStore->create('Test/testBat.yml', $manifest, $manifestPath);
                },
                'expectedNextNonCompiledSource' => null,
            ],
        ];
    }
}
