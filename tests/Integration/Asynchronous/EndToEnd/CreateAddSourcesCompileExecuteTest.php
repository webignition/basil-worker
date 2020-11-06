<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Entity\Job;
use App\Entity\Test;
use App\Repository\TestRepository;
use App\Services\JobStore;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;

class CreateAddSourcesCompileExecuteTest extends AbstractBaseIntegrationTest
{
    private ClientRequestSender $clientRequestSender;
    private JobStore $jobStore;
    private UploadedFileFactory $uploadedFileFactory;
    private BasilFixtureHandler $basilFixtureHandler;
    private TestRepository $testRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        self::assertInstanceOf(ClientRequestSender::class, $clientRequestSender);
        if ($clientRequestSender instanceof ClientRequestSender) {
            $this->clientRequestSender = $clientRequestSender;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }

        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        self::assertInstanceOf(UploadedFileFactory::class, $uploadedFileFactory);
        if ($uploadedFileFactory instanceof UploadedFileFactory) {
            $this->uploadedFileFactory = $uploadedFileFactory;
        }

        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        self::assertInstanceOf(BasilFixtureHandler::class, $basilFixtureHandler);
        if ($basilFixtureHandler instanceof BasilFixtureHandler) {
            $this->basilFixtureHandler = $basilFixtureHandler;
        }

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
        $createJobResponse = $this->clientRequestSender->createJob($label, $callbackUrl);
        self::assertSame(200, $createJobResponse->getStatusCode());
        self::assertTrue($this->jobStore->hasJob());

        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $addJobSourcesResponse = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest($manifestPath),
            $this->basilFixtureHandler->createUploadFileCollection($sourcePaths)
        );
        self::assertSame(200, $addJobSourcesResponse->getStatusCode());

        $job = $this->jobStore->getJob();
        self::assertSame($sourcePaths, $job->getSources());

        // @todo: replace with less brittle, more elegant solution in #329
        sleep(6);

        $this->entityManager->refresh($job);

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
}
