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
        $this->doCreateJobAddSourcesTest(
            $label,
            $callbackUrl,
            $manifestPath,
            $sourcePaths,
            function (Job $job, string $expectedJobEndState) {
                $this->entityManager->refresh($job);

                return $expectedJobEndState === $job->getState();
            },
            [
                $expectedJobEndState,
            ],
            $expectedJobEndState,
            function (array $expectedTestEndStates) {
                $tests = $this->testRepository->findAll();
                self::assertCount(count($expectedTestEndStates), $tests);

                foreach ($tests as $testIndex => $test) {
                    $expectedTestEndState = $expectedTestEndStates[$testIndex] ?? null;
                    self::assertSame($expectedTestEndState, $test->getState());
                }
            },
            [
                $expectedTestEndStates,
            ]
        );
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
}
