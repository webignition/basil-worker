<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\JobConfiguration;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    protected ClientRequestSender $clientRequestSender;
    protected JobStore $jobStore;
    protected UploadedFileFactory $uploadedFileFactory;
    protected BasilFixtureHandler $basilFixtureHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
        $this->initializeSourceStore();
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param string[] $expectedSourcePaths
     * @param InvokableInterface $waitUntil
     * @param Job::STATE_* $expectedJobEndState
     * @param InvokableInterface $postAssertions
     */
    protected function doCreateJobAddSourcesTest(
        JobConfiguration $jobConfiguration,
        array $expectedSourcePaths,
        InvokableInterface $waitUntil,
        string $expectedJobEndState,
        InvokableInterface $postAssertions
    ): void {
        $this->createJob($jobConfiguration->getLabel(), $jobConfiguration->getCallbackUrl());

        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $this->addJobSources($jobConfiguration->getManifestPath());

        $job = $this->jobStore->getJob();
        self::assertSame($expectedSourcePaths, $job->getSources());

        $this->waitUntil(
            function (Job $job) use ($waitUntil): bool {
                return $waitUntil($job);
            }
        );

        self::assertSame($expectedJobEndState, $job->getState());

        foreach ($postAssertions->getServiceReferences() as $serviceReference) {
            $service = self::$container->get($serviceReference->getId());
            if (null !== $service) {
                $postAssertions->replaceServiceReference($serviceReference, $service);
            }
        }

        $postAssertions();
    }

    protected function createJob(string $label, string $callbackUrl): Response
    {
        $response = $this->clientRequestSender->createJob($label, $callbackUrl);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->jobStore->hasJob());

        return $response;
    }

    protected function addJobSources(string $manifestPath): Response
    {
        $manifestContent = (string) file_get_contents($manifestPath);
        $sourcePaths = array_filter(explode("\n", $manifestContent));

        $response = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest($manifestPath),
            $this->basilFixtureHandler->createUploadFileCollection($sourcePaths)
        );

        self::assertInstanceOf(JsonResponse::class, $response);
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
