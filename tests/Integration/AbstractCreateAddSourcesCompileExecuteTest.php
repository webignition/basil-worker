<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Services\ApplicationStateHandler;
use App\Tests\Services\Asserter\JsonResponseAsserter;
use App\Tests\Services\Asserter\SystemStateAsserter;
use App\Tests\Services\CallableInvoker;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\FileStoreHandler;
use App\Tests\Services\IntegrationJobProperties;
use App\Tests\Services\UploadedFileFactory;
use SebastianBergmann\Timer\Timer;
use App\Services\ApplicationState;
use App\Services\CompilationState;
use App\Services\ExecutionState;

abstract class AbstractCreateAddSourcesCompileExecuteTest extends AbstractBaseIntegrationTest
{
    private const MAX_DURATION_IN_SECONDS = 30;

    private CallableInvoker $callableInvoker;
    private FileStoreHandler $localSourceStoreHandler;
    private FileStoreHandler $uploadStoreHandler;
    private ClientRequestSender $clientRequestSender;
    private JsonResponseAsserter $jsonResponseAsserter;
    private SystemStateAsserter $systemStateAsserter;
    private ApplicationStateHandler $applicationStateHandler;
    private IntegrationJobProperties $jobProperties;

    protected function setUp(): void
    {
        parent::setUp();

        $callableInvoker = self::$container->get(CallableInvoker::class);
        \assert($callableInvoker instanceof CallableInvoker);
        $this->callableInvoker = $callableInvoker;

        $localSourceStoreHandler = self::$container->get('app.tests.services.file_store_handler.local_source');
        \assert($localSourceStoreHandler instanceof FileStoreHandler);
        $this->localSourceStoreHandler = $localSourceStoreHandler;
        $this->localSourceStoreHandler->clear();

        $uploadStoreHandler = self::$container->get('app.tests.services.file_store_handler.uploaded');
        \assert($uploadStoreHandler instanceof FileStoreHandler);
        $this->uploadStoreHandler = $uploadStoreHandler;
        $this->uploadStoreHandler->clear();

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        \assert($clientRequestSender instanceof ClientRequestSender);
        $this->clientRequestSender = $clientRequestSender;

        $jsonResponseAsserter = self::$container->get(JsonResponseAsserter::class);
        \assert($jsonResponseAsserter instanceof JsonResponseAsserter);
        $this->jsonResponseAsserter = $jsonResponseAsserter;

        $systemStateAsserter = self::$container->get(SystemStateAsserter::class);
        \assert($systemStateAsserter instanceof SystemStateAsserter);
        $this->systemStateAsserter = $systemStateAsserter;

        $applicationStateHandler = self::$container->get(ApplicationStateHandler::class);
        \assert($applicationStateHandler instanceof ApplicationStateHandler);
        $this->applicationStateHandler = $applicationStateHandler;

        $jobProperties = self::$container->get(IntegrationJobProperties::class);
        \assert($jobProperties instanceof IntegrationJobProperties);
        $this->jobProperties = $jobProperties;

        $this->entityRemover->removeAll();
    }

    protected function tearDown(): void
    {
        $this->entityRemover->removeAll();
        $this->localSourceStoreHandler->clear();
        $this->uploadStoreHandler->clear();

        parent::tearDown();
    }

    /**
     * @dataProvider createAddSourcesCompileExecuteDataProvider
     *
     * @param string[]                  $sourcePaths
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_*   $expectedExecutionEndState
     * @param ApplicationState::STATE_* $expectedApplicationEndState
     */
    public function testCreateAddSourcesCompileExecute(
        int $jobMaximumDurationInSeconds,
        string $manifestPath,
        array $sourcePaths,
        ?callable $postAddSources,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        string $expectedApplicationEndState,
        ?callable $assertions = null
    ): void {
        $statusResponse = $this->clientRequestSender->getStatus();
        $this->jsonResponseAsserter->assertJsonResponse(400, [], $statusResponse);

        $label = $this->jobProperties->getLabel();
        $callbackUrl = $this->jobProperties->getCallbackUrl();

        $createResponse = $this->clientRequestSender->createJob($label, $callbackUrl, $jobMaximumDurationInSeconds);
        $this->jsonResponseAsserter->assertJsonResponse(200, (object) [], $createResponse);

        $expectedJobProperties = [
            'label' => $label,
            'callback_url' => $callbackUrl,
            'maximum_duration_in_seconds' => $jobMaximumDurationInSeconds,
        ];

        $statusResponse = $this->clientRequestSender->getStatus();

        $this->jsonResponseAsserter->assertJsonResponse(
            200,
            array_merge(
                $expectedJobProperties,
                [
                    'compilation_state' => CompilationState::STATE_AWAITING,
                    'execution_state' => ExecutionState::STATE_AWAITING,
                    'sources' => [],
                    'tests' => [],
                ]
            ),
            $statusResponse
        );

        $this->systemStateAsserter->assertSystemState(
            CompilationState::STATE_AWAITING,
            ExecutionState::STATE_AWAITING,
            ApplicationState::STATE_AWAITING_SOURCES
        );

        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        \assert($uploadedFileFactory instanceof UploadedFileFactory);

        $uploadedFileCollection = $uploadedFileFactory->createCollection(
            $this->uploadStoreHandler->copyFixtures($sourcePaths)
        );

        $addSourcesResponse = $this->clientRequestSender->addJobSources(
            $uploadedFileFactory->createForManifest($manifestPath),
            $uploadedFileCollection
        );

        $this->jsonResponseAsserter->assertJsonResponse(200, (object) [], $addSourcesResponse);

        $timer = new Timer();
        $timer->start();

        if (is_callable($postAddSources)) {
            $this->callableInvoker->invoke($postAddSources);
        }

        $this->applicationStateHandler->waitUntilStateIs([
            ApplicationState::STATE_COMPLETE,
            ApplicationState::STATE_TIMED_OUT,
        ]);

        $duration = $timer->stop();

        $this->systemStateAsserter->assertSystemState(
            $expectedCompilationEndState,
            $expectedExecutionEndState,
            $expectedApplicationEndState
        );

        self::assertLessThanOrEqual(self::MAX_DURATION_IN_SECONDS, $duration->asSeconds());

        if (is_callable($assertions)) {
            $this->callableInvoker->invoke($assertions);
        }
    }

    /**
     * @return array[]
     */
    abstract public function createAddSourcesCompileExecuteDataProvider(): array;
}
