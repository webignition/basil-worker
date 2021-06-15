<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous\EndToEnd;

use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Services\ApplicationStateHandler;
use App\Tests\Services\Asserter\JsonResponseAsserter;
use App\Tests\Services\Asserter\SystemStateAsserter;
use App\Tests\Services\CallableInvoker;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\FileStoreHandler;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\UploadedFileFactory;
use Psr\Http\Message\RequestInterface;
use SebastianBergmann\Timer\Timer;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\BasilWorker\PersistenceBundle\Services\Repository\TestRepository;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;
use webignition\BasilWorker\StateBundle\Services\CompilationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;

class CreateAddSourcesCompileExecuteTest extends AbstractBaseIntegrationTest
{
    private const MAX_DURATION_IN_SECONDS = 30;

    private ClientRequestSender $clientRequestSender;
    private SystemStateAsserter $systemStateAsserter;
    private JsonResponseAsserter $jsonResponseAsserter;
    private FileStoreHandler $uploadStoreHandler;
    private ApplicationStateHandler $applicationStateHandler;
    private CallableInvoker $callableInvoker;
    private FileStoreHandler $localSourceStoreHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        \assert($clientRequestSender instanceof ClientRequestSender);
        $this->clientRequestSender = $clientRequestSender;

        $systemStateAsserter = self::$container->get(SystemStateAsserter::class);
        \assert($systemStateAsserter instanceof SystemStateAsserter);
        $this->systemStateAsserter = $systemStateAsserter;

        $jsonResponseAsserter = self::$container->get(JsonResponseAsserter::class);
        \assert($jsonResponseAsserter instanceof JsonResponseAsserter);
        $this->jsonResponseAsserter = $jsonResponseAsserter;

        $localSourceStoreHandler = self::$container->get('app.tests.services.file_store_handler.local_source');
        \assert($localSourceStoreHandler instanceof FileStoreHandler);
        $this->localSourceStoreHandler = $localSourceStoreHandler;
        $this->localSourceStoreHandler->clear();

        $uploadStoreHandler = self::$container->get('app.tests.services.file_store_handler.uploaded');
        \assert($uploadStoreHandler instanceof FileStoreHandler);
        $this->uploadStoreHandler = $uploadStoreHandler;
        $this->uploadStoreHandler->clear();

        $applicationStateHandler = self::$container->get(ApplicationStateHandler::class);
        \assert($applicationStateHandler instanceof ApplicationStateHandler);
        $this->applicationStateHandler = $applicationStateHandler;

        $callableInvoker = self::$container->get(CallableInvoker::class);
        \assert($callableInvoker instanceof CallableInvoker);
        $this->callableInvoker = $callableInvoker;
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
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        string $expectedApplicationEndState,
        ?callable $assertions = null
    ): void {
        $statusResponse = $this->clientRequestSender->getStatus();
        $this->jsonResponseAsserter->assertJsonResponse(400, [], $statusResponse);

        $label = md5('label content');
        $callbackUrl = ($_ENV['CALLBACK_BASE_URL'] ?? '') . '/status/200';

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
    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        return [
            'default' => [
                'jobMaximumDurationInSeconds' => 99,
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'sourcePaths' => [
                    'Page/index.yml',
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_COMPLETE,
                'expectedApplicationEndState' => ApplicationState::STATE_COMPLETE,
            ],
            'verify job is timed out' => [
                'jobMaximumDurationInSeconds' => 1,
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'sourcePaths' => [
                    'Page/index.yml',
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_CANCELLED,
                'expectedApplicationEndState' => ApplicationState::STATE_TIMED_OUT,
                'assertions' => function (TestRepository $testRepository, HttpLogReader $httpLogReader) {
                    // Verify job and test end states
                    $tests = $testRepository->findAll();
                    $hasFoundCancelledTest = false;

                    foreach ($tests as $test) {
                        if (Test::STATE_CANCELLED === $test->getState() && false === $hasFoundCancelledTest) {
                            $hasFoundCancelledTest = true;
                        }

                        if ($hasFoundCancelledTest) {
                            self::assertSame(Test::STATE_CANCELLED, $test->getState());
                        } else {
                            self::assertSame(Test::STATE_COMPLETE, $test->getState());
                        }
                    }

                    self::assertTrue($hasFoundCancelledTest);

                    // Verify final HTTP request
                    // Fixes #676. Wait (0.05 seconds) for the HTTP transaction log to be written to fully.
                    usleep(50000);

                    $httpTransactions = $httpLogReader->getTransactions();
                    $httpLogReader->reset();

                    $lastRequestPayload = [];
                    $lastRequest = $httpTransactions->getRequests()->getLast();
                    if ($lastRequest instanceof RequestInterface) {
                        $lastRequestPayload = json_decode($lastRequest->getBody()->getContents(), true);
                    }

                    self::assertSame(CallbackInterface::TYPE_JOB_TIME_OUT, $lastRequestPayload['type']);
                },
            ],
        ];
    }
}
