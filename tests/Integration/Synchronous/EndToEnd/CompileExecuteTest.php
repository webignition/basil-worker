<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\EndToEnd;

use App\Message\JobReadyMessage;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\JobSetup;
use App\Tests\Services\CallableInvoker;
use App\Tests\Services\EntityRefresher;
use App\Tests\Services\EnvironmentFactory;
use App\Tests\Services\FileStoreHandler;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\SourceFactory;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use SebastianBergmann\Timer\Timer;
use Symfony\Component\Messenger\MessageBusInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Store\SourceStore;
use webignition\BasilWorker\StateBundle\Services\ApplicationState;
use webignition\BasilWorker\StateBundle\Services\CompilationState;
use webignition\BasilWorker\StateBundle\Services\ExecutionState;
use webignition\HttpHistoryContainer\Collection\RequestCollection;
use webignition\HttpHistoryContainer\Collection\RequestCollectionInterface;

class CompileExecuteTest extends AbstractBaseIntegrationTest
{
    private const MAX_DURATION_IN_SECONDS = 30;

    private CallableInvoker $callableInvoker;
    private EnvironmentFactory $environmentFactory;
    private SourceFactory $sourceFactory;
    private MessageBusInterface $messageBus;
    private SourceStore $sourceStore;
    private EntityRefresher $entityRefresher;
    private FileStoreHandler $localSourceStoreHandler;
    private FileStoreHandler $uploadStoreHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $callableInvoker = self::$container->get(CallableInvoker::class);
        \assert($callableInvoker instanceof CallableInvoker);
        $this->callableInvoker = $callableInvoker;

        $environmentFactory = self::$container->get(EnvironmentFactory::class);
        \assert($environmentFactory instanceof EnvironmentFactory);
        $this->environmentFactory = $environmentFactory;

        $sourceFactory = self::$container->get(SourceFactory::class);
        \assert($sourceFactory instanceof SourceFactory);
        $this->sourceFactory = $sourceFactory;

        $messageBus = self::$container->get(MessageBusInterface::class);
        \assert($messageBus instanceof MessageBusInterface);
        $this->messageBus = $messageBus;

        $sourceStore = self::$container->get(SourceStore::class);
        \assert($sourceStore instanceof SourceStore);
        $this->sourceStore = $sourceStore;

        $entityRefresher = self::$container->get(EntityRefresher::class);
        \assert($entityRefresher instanceof EntityRefresher);
        $this->entityRefresher = $entityRefresher;

        $localSourceStoreHandler = self::$container->get('app.tests.services.file_store_handler.local_source');
        \assert($localSourceStoreHandler instanceof FileStoreHandler);
        $this->localSourceStoreHandler = $localSourceStoreHandler;
        $this->localSourceStoreHandler->clear();

        $uploadStoreHandler = self::$container->get('app.tests.services.file_store_handler.uploaded');
        \assert($uploadStoreHandler instanceof FileStoreHandler);
        $this->uploadStoreHandler = $uploadStoreHandler;
        $this->uploadStoreHandler->clear();

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
     * @param CompilationState::STATE_* $expectedCompilationEndState
     * @param ExecutionState::STATE_*   $expectedExecutionEndState
     * @param ApplicationState::STATE_* $expectedApplicationEndState
     */
    public function testCreateAddSourcesCompileExecute(
        EnvironmentSetup $setup,
        string $expectedCompilationEndState,
        string $expectedExecutionEndState,
        string $expectedApplicationEndState,
        callable $assertions
    ): void {
        $this->environmentFactory->create($setup);

        $this->assertSystemState(
            CompilationState::STATE_AWAITING,
            ExecutionState::STATE_AWAITING,
            ApplicationState::STATE_AWAITING_SOURCES
        );

        $jobSetup = $setup->getJobSetup();
        $localSourcePaths = $jobSetup->getLocalSourcePaths();
        foreach ($localSourcePaths as $localSourcePath) {
            $this->localSourceStoreHandler->copyFixture($localSourcePath);
            $this->sourceFactory->createFromSourcePath($localSourcePath);
        }

        $timer = new Timer();
        $timer->start();

        $this->messageBus->dispatch(new JobReadyMessage());

        self::assertSame($localSourcePaths, $this->sourceStore->findAllPaths());

        $intervalInMicroseconds = 100000;
        while (false === $this->isApplicationFinished()) {
            usleep($intervalInMicroseconds);
        }

        $duration = $timer->stop();

        $this->assertSystemState(
            $expectedCompilationEndState,
            $expectedExecutionEndState,
            $expectedApplicationEndState
        );

        $this->entityRefresher->refresh();
        $this->callableInvoker->invoke($assertions);

        self::assertLessThanOrEqual(self::MAX_DURATION_IN_SECONDS, $duration->asSeconds());
    }

    /**
     * @return array[]
     */
    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        $label = md5('label content');
        $callbackUrl = ($_ENV['CALLBACK_BASE_URL'] ?? '') . '/status/200';

        return [
            'default' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())
                            ->withLabel($label)
                            ->withCallbackUrl($callbackUrl)
                            ->withLocalSourcePaths([
                                'Page/index.yml',
                                'Test/chrome-open-index.yml',
                                'Test/chrome-firefox-open-index.yml',
                                'Test/chrome-open-form.yml',
                            ]),
                    ),
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_COMPLETE,
                'expectedApplicationEndState' => ApplicationState::STATE_COMPLETE,
                'assertions' => function (HttpLogReader $httpLogReader) use ($label, $callbackUrl) {
                    $expectedHttpRequests = new RequestCollection([
                        'job/started' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_JOB_STARTED,
                            []
                        ),
                        'compilation/started: chrome-open-index' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_STARTED,
                            [
                                'source' => 'Test/chrome-open-index.yml',
                            ]
                        ),
                        'compilation/passed: chrome-open-index' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_PASSED,
                            [
                                'source' => 'Test/chrome-open-index.yml',
                            ]
                        ),
                        'compilation/started: chrome-firefox-open-index' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_STARTED,
                            [
                                'source' => 'Test/chrome-firefox-open-index.yml',
                            ]
                        ),
                        'compilation/passed: chrome-firefox-open-index' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_PASSED,
                            [
                                'source' => 'Test/chrome-firefox-open-index.yml',
                            ]
                        ),
                        'compilation/started: chrome--open-form' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_STARTED,
                            [
                                'source' => 'Test/chrome-open-form.yml',
                            ]
                        ),
                        'compilation/passed: chrome--open-form' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_PASSED,
                            [
                                'source' => 'Test/chrome-open-form.yml',
                            ]
                        ),
                        'compilation/completed' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_COMPILATION_SUCCEEDED,
                            []
                        ),
                        'execution/started' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_EXECUTION_STARTED,
                            []
                        ),
                        'test/started: chrome-open-index' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_STARTED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-open-index.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'step/passed: chrome-open-index: open' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_STEP_PASSED,
                            [
                                'type' => 'step',
                                'name' => 'verify page is open',
                                'status' => 'passed',
                                'statements' => [
                                    [
                                        'type' => 'assertion',
                                        'source' => '$page.url is "http://nginx-html/index.html"',
                                        'status' => 'passed',
                                        'transformations' => [
                                            [
                                                'type' => 'resolution',
                                                'source' => '$page.url is $index.url'
                                            ]
                                        ],
                                    ],
                                ],
                            ]
                        ),
                        'test/passed: chrome-open-index' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_PASSED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-open-index.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'test/started: chrome-firefox-open-index: chrome' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_STARTED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'step/passed: chrome-firefox-open-index: chrome, open' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_STEP_PASSED,
                            [
                                'type' => 'step',
                                'name' => 'verify page is open',
                                'status' => 'passed',
                                'statements' => [
                                    [
                                        'type' => 'assertion',
                                        'source' => '$page.url is "http://nginx-html/index.html"',
                                        'status' => 'passed',
                                    ],
                                ],
                            ]
                        ),
                        'test/passed: chrome-firefox-open-index: chrome' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_PASSED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'test/started: chrome-firefox-open-index: firefox' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_STARTED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'firefox',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'step/passed: chrome-firefox-open-index: firefox open' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_STEP_PASSED,
                            [
                                'type' => 'step',
                                'name' => 'verify page is open',
                                'status' => 'passed',
                                'statements' => [
                                    [
                                        'type' => 'assertion',
                                        'source' => '$page.url is "http://nginx-html/index.html"',
                                        'status' => 'passed',
                                    ],
                                ],
                            ]
                        ),
                        'test/passed: chrome-firefox-open-index: firefox' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_PASSED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-firefox-open-index.yml',
                                'config' => [
                                    'browser' => 'firefox',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'test/started: chrome-open-form' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_STARTED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-open-form.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/form.html',
                                ],
                            ]
                        ),
                        'step/passed: chrome-open-form: open' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_STEP_PASSED,
                            [
                                'type' => 'step',
                                'name' => 'verify page is open',
                                'status' => 'passed',
                                'statements' => [
                                    [
                                        'type' => 'assertion',
                                        'source' => '$page.url is "http://nginx-html/form.html"',
                                        'status' => 'passed',
                                    ],
                                ],
                            ]
                        ),
                        'test/passed: chrome-open-form' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_PASSED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-open-form.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/form.html',
                                ],
                            ]
                        ),
                        'execution/completed' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_EXECUTION_COMPLETED,
                            []
                        ),
                        'job/completed' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_JOB_COMPLETED,
                            []
                        ),
                    ]);

                    $transactions = $httpLogReader->getTransactions();
                    $httpLogReader->reset();

                    $this->assertRequestCollectionsAreEquivalent($expectedHttpRequests, $transactions->getRequests());
                },
            ],
            'step failed' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())
                            ->withLabel($label)
                            ->withCallbackUrl($callbackUrl)
                            ->withLocalSourcePaths([
                                'Test/chrome-open-index-with-step-failure.yml',
                            ]),
                    ),
                'expectedCompilationEndState' => CompilationState::STATE_COMPLETE,
                'expectedExecutionEndState' => ExecutionState::STATE_CANCELLED,
                'expectedApplicationEndState' => ApplicationState::STATE_COMPLETE,
                'assertions' => function (HttpLogReader $httpLogReader) use ($label, $callbackUrl) {
                    $transactions = $httpLogReader->getTransactions();
                    $httpLogReader->reset();

                    $expectedHttpRequests = new RequestCollection([
                        'step/failed' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_STEP_FAILED,
                            [
                                'type' => 'step',
                                'name' => 'fail on intentionally-missing element',
                                'status' => 'failed',
                                'statements' => [
                                    [
                                        'type' => 'assertion',
                                        'source' => '$".non-existent" exists',
                                        'status' => 'failed',
                                        'summary' => [
                                            'operator' => 'exists',
                                            'source' => [
                                                'type' => 'node',
                                                'body' => [
                                                    'type' => 'element',
                                                    'identifier' => [
                                                        'source' => '$".non-existent"',
                                                        'properties' => [
                                                            'type' => 'css',
                                                            'locator' => '.non-existent',
                                                            'position' => 1,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ]
                        ),
                        'test/failed' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_TEST_FAILED,
                            [
                                'type' => 'test',
                                'path' => 'Test/chrome-open-index-with-step-failure.yml',
                                'config' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://nginx-html/index.html',
                                ],
                            ]
                        ),
                        'job/failed' => $this->createExpectedRequest(
                            $label,
                            $callbackUrl,
                            CallbackInterface::TYPE_JOB_FAILED,
                            []
                        ),
                    ]);

                    $transactions = $transactions->slice(
                        -1 * $expectedHttpRequests->count(),
                        null
                    );

                    $requests = $transactions->getRequests();

                    self::assertCount(count($expectedHttpRequests), $requests);
                    $this->assertRequestCollectionsAreEquivalent($expectedHttpRequests, $requests);
                },
            ],
        ];
    }

    private function assertRequestCollectionsAreEquivalent(
        RequestCollectionInterface $expectedRequests,
        RequestCollectionInterface $requests
    ): void {
        $requestsIterator = $requests->getIterator();

        foreach ($expectedRequests as $requestIndex => $expectedRequest) {
            $request = $requestsIterator->current();
            $requestsIterator->next();

            self::assertInstanceOf(RequestInterface::class, $request);
            $this->assertRequestsAreEquivalent($expectedRequest, $request, $requestIndex);
        }
    }

    private function assertRequestsAreEquivalent(
        RequestInterface $expected,
        RequestInterface $actual,
        int $requestIndex
    ): void {
        self::assertSame(
            $expected->getMethod(),
            $actual->getMethod(),
            'Method of request at index ' . $requestIndex . ' not as expected'
        );

        self::assertSame(
            (string) $expected->getUri(),
            (string) $actual->getUri(),
            'URL of request at index ' . $requestIndex . ' not as expected'
        );

        self::assertSame(
            $expected->getHeaderLine('content-type'),
            $actual->getHeaderLine('content-type'),
            'Content-type header of request at index ' . $requestIndex . ' not as expected'
        );

        self::assertSame(
            json_decode($expected->getBody()->getContents(), true),
            json_decode($actual->getBody()->getContents(), true),
            'Body of request at index ' . $requestIndex . ' not as expected'
        );
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed>              $payload
     */
    private function createExpectedRequest(
        string $label,
        string $callbackUrl,
        string $type,
        array $payload
    ): RequestInterface {
        return new Request(
            'POST',
            $callbackUrl,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode([
                'label' => $label,
                'type' => $type,
                'payload' => $payload,
            ])
        );
    }

    private function isApplicationFinished(): bool
    {
        $this->entityRefresher->refresh();

        $applicationState = self::$container->get(ApplicationState::class);
        \assert($applicationState instanceof ApplicationState);

        return in_array((string) $applicationState, [
            ApplicationState::STATE_COMPLETE,
            ApplicationState::STATE_TIMED_OUT,
        ]);
    }

    /**
     * @param CompilationState::STATE_* $expectedCompilationState
     * @param ExecutionState::STATE_*   $expectedExecutionState
     * @param ApplicationState::STATE_* $expectedApplicationState
     */
    private function assertSystemState(
        string $expectedCompilationState,
        string $expectedExecutionState,
        string $expectedApplicationState
    ): void {
        $this->entityRefresher->refresh();

        $compilationState = self::$container->get(CompilationState::class);
        \assert($compilationState instanceof CompilationState);
        self::assertSame($expectedCompilationState, (string) $compilationState);

        $executionState = self::$container->get(ExecutionState::class);
        \assert($executionState instanceof ExecutionState);
        self::assertSame($expectedExecutionState, (string) $executionState);

        $applicationState = self::$container->get(ApplicationState::class);
        \assert($applicationState instanceof ApplicationState);
        self::assertSame($expectedApplicationState, (string) $applicationState);
    }
}
