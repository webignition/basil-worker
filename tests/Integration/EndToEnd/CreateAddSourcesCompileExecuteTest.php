<?php

declare(strict_types=1);

namespace App\Tests\Integration\EndToEnd;

use App\Entity\Job;
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

        $this->initializeSourceStore();
    }

    public function testCreateAddSourcesCompileExecute()
    {
        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';

        $sources = [
            'Test/chrome-open-index.yml',
            'Test/chrome-firefox-open-index.yml',
            'Test/chrome-open-form.yml',
        ];

        $manifestPath = getcwd() . '/tests/Fixtures/Manifest/manifest.txt';

        $createJobResponse = $this->clientRequestSender->createJob($label, $callbackUrl);
        self::assertSame(200, $createJobResponse->getStatusCode());
        self::assertTrue($this->jobStore->hasJob());

        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $addJobSourcesResponse = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest($manifestPath),
            $this->basilFixtureHandler->createUploadFileCollection($sources)
        );
        self::assertSame(200, $addJobSourcesResponse->getStatusCode());

        $job = $this->jobStore->getJob();
        self::assertSame($sources, $job->getSources());
        self::assertSame(Job::STATE_EXECUTION_RUNNING, $job->getState());

        // @todo: verify execution in #264
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
