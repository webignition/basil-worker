<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Entity\Job;
use App\Entity\Test;
use App\Repository\TestRepository;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Services\SourceStoreInitializer;

class CreateAddSourcesCompileExecuteTest extends AbstractEndToEndTest
{
    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $testRepository = self::$container->get(TestRepository::class);
        self::assertInstanceOf(TestRepository::class, $testRepository);
        if ($testRepository instanceof TestRepository) {
            $this->testRepository = $testRepository;
        }

        $this->initializeSourceStore();
    }

    /**
     * @dataProvider createAddSourcesCompileExecuteDataProvider
     *
     * @param string $label
     * @param string $callbackUrl
     * @param string $manifestPath
     * @param string[] $sourcePaths
     * @param Job::STATE_* $expectedJobEndState
     * @param array<Test::STATE_*> $expectedTestEndStates
     */
    public function testCreateAddSourcesCompileExecute(
        string $label,
        string $callbackUrl,
        string $manifestPath,
        array $sourcePaths,
        string $expectedJobEndState,
        array $expectedTestEndStates
    ) {
        $this->createJob($label, $callbackUrl);

        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $this->addJobSources($manifestPath, $sourcePaths);

        $job = $this->jobStore->getJob();
        self::assertSame($sourcePaths, $job->getSources());

        $this->waitUntil(function () use ($job, $expectedJobEndState): bool {
            $this->entityManager->refresh($job);

            return $expectedJobEndState === $job->getState();
        });

        self::assertSame($expectedJobEndState, $job->getState());

        $tests = $this->testRepository->findAll();
        self::assertCount(count($expectedTestEndStates), $tests);

        foreach ($tests as $testIndex => $test) {
            $expectedTestEndState = $expectedTestEndStates[$testIndex] ?? null;
            self::assertSame($expectedTestEndState, $test->getState());
        }
    }

    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        return [
            'default' => [
                'label' => md5('label content'),
                'callbackUrl' => 'http://example.com/callback',
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'sourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedJobEndState' => Job::STATE_EXECUTION_COMPLETE,
                'expectedTestEndState' => [
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                    Test::STATE_COMPLETE,
                ],
            ],
        ];
    }

    private function initializeSourceStore(): void
    {
        $sourceStoreInitializer = self::$container->get(SourceStoreInitializer::class);
        self::assertInstanceOf(SourceStoreInitializer::class, $sourceStoreInitializer);
        if ($sourceStoreInitializer instanceof SourceStoreInitializer) {
            $sourceStoreInitializer->initialize();
        }
    }

    private function waitUntil(callable $callable, int $maxDurationInSeconds = 30): bool
    {
        $duration = 0;
        $maxDuration = $maxDurationInSeconds * 1000000;
        $maxDurationReached = $duration >= $maxDuration;
        $intervalInMicroseconds = 100000;

        while (false === $callable() && false === $maxDurationReached) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;
            $maxDurationReached = $duration >= $maxDuration;

            if ($maxDurationReached) {
                return false;
            }
        }

        return true;
    }
}
