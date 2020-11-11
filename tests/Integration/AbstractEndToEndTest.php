<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    protected ClientRequestSender $clientRequestSender;
    protected JobStore $jobStore;
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

    protected function doCreateJobAddSourcesTest(
        string $label,
        string $callbackUrl,
        string $manifestPath,
        array $expectedSourcePaths,
        callable $waitUntil,
        array $waitUntilArgs,
        string $expectedJobEndState,
        callable $postAssertions,
        array $postAssertionsArgs
    ) {
        $this->createJob($label, $callbackUrl);

        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $this->addJobSources($manifestPath);

        $job = $this->jobStore->getJob();
        self::assertSame($expectedSourcePaths, $job->getSources());

        $this->waitUntil(
            function (Job $job) use ($waitUntil, $waitUntilArgs): bool {
                return $waitUntil($job, ...$waitUntilArgs);
            }
        );

        self::assertSame($expectedJobEndState, $job->getState());

        $postAssertions(...$postAssertionsArgs);
    }

    protected function createJob(string $label, string $callbackUrl): JsonResponse
    {
        $response = $this->clientRequestSender->createJob($label, $callbackUrl);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->jobStore->hasJob());

        return $response;
    }

    protected function addJobSources(string $manifestPath): JsonResponse
    {
        $manifestContent = file_get_contents($manifestPath);
        $sourcePaths = array_filter(explode("\n", $manifestContent));

        $response = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest($manifestPath),
            $this->basilFixtureHandler->createUploadFileCollection($sourcePaths)
        );

        self::assertSame(200, $response->getStatusCode());

        return $response;
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

        $job = $this->jobStore->getJob();

        while (false === $callable($job) && false === $maxDurationReached) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;
            $maxDurationReached = $duration >= $maxDuration;

            if ($maxDurationReached) {
                return false;
            }

            $job = $this->jobStore->getJob();
        }

        return true;
    }
}
