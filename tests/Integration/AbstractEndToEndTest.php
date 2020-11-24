<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Job;
use App\Entity\Test;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;
use App\Services\JobStore;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\JobConfiguration;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\EntityRefresher;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\InvokableFactory\CompilationStateGetterFactory;
use App\Tests\Services\InvokableFactory\ExecutionStateGetterFactory;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use SebastianBergmann\Timer\Timer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    use TestClassServicePropertyInjectorTrait;

    private const MAX_DURATION_IN_SECONDS = 30;
    private const MICROSECONDS_PER_SECOND = 1000000;

    protected ClientRequestSender $clientRequestSender;
    protected JobStore $jobStore;
    protected UploadedFileFactory $uploadedFileFactory;
    protected BasilFixtureHandler $basilFixtureHandler;
    protected EntityRefresher $entityRefresher;
    protected HttpLogReader $httpLogReader;
    protected InvokableHandler $invokableHandler;
    protected ApplicationState $applicationState;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
        $this->initializeSourceStore();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->httpLogReader->reset();
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param string[] $expectedSourcePaths
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_* $expectedExecutionEndState
     * @param InvokableInterface $postAssertions
     */
    protected function doCreateJobAddSourcesTest(
        JobConfiguration $jobConfiguration,
        array $expectedSourcePaths,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        InvokableInterface $postAssertions
    ): void {
        $this->createJob($jobConfiguration->getLabel(), $jobConfiguration->getCallbackUrl());

        self::assertSame(
            CompilationState::STATE_AWAITING,
            $this->invokableHandler->invoke(CompilationStateGetterFactory::get())
        );

        $timer = new Timer();
        $timer->start();

        $this->addJobSources($jobConfiguration->getManifestPath());

        $job = $this->jobStore->getJob();
        self::assertSame($expectedSourcePaths, $job->getSources());

        $this->waitUntilApplicationWorkflowIsComplete();

        $duration = $timer->stop();

        self::assertSame(
            $expectedCompilationEndState,
            $this->invokableHandler->invoke(CompilationStateGetterFactory::get())
        );

        self::assertSame(
            $expectedExecutionEndState,
            $this->invokableHandler->invoke(ExecutionStateGetterFactory::get())
        );

        foreach ($postAssertions->getServiceReferences() as $serviceReference) {
            $service = self::$container->get($serviceReference->getId());
            if (null !== $service) {
                $postAssertions->replaceServiceReference($serviceReference, $service);
            }
        }

        $postAssertions();
        self::assertLessThanOrEqual(self::MAX_DURATION_IN_SECONDS, $duration->asSeconds());
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

    private function waitUntilApplicationWorkflowIsComplete(): bool
    {
        $duration = 0;
        $maxDuration = self::MAX_DURATION_IN_SECONDS * self::MICROSECONDS_PER_SECOND;
        $maxDurationReached = false;
        $intervalInMicroseconds = 100000;

        while (
            false === $this->applicationState->is(ApplicationState::STATE_COMPLETE) &&
            false === $maxDurationReached
        ) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;
            $maxDurationReached = $duration >= $maxDuration;

            if ($maxDurationReached) {
                return false;
            }

            $this->entityRefresher->refreshForEntities([
                Job::class,
                Test::class,
                CallbackEntity::class,
            ]);
        }

        return true;
    }
}
