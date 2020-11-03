<?php

declare(strict_types=1);

namespace App\Tests\Integration\EndToEnd;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Transaction\HttpTransaction;

class CreateAddSourcesCompileExecuteTest extends AbstractBaseIntegrationTest
{
    private ClientRequestSender $clientRequestSender;
    private JobStore $jobStore;
    private UploadedFileFactory $uploadedFileFactory;
    private BasilFixtureHandler $basilFixtureHandler;
    private HttpLogReader $httpLogReader;

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

        $httpLogReader = self::$container->get(HttpLogReader::class);
        self::assertInstanceOf(HttpLogReader::class, $httpLogReader);
        if ($httpLogReader instanceof HttpLogReader) {
            $this->httpLogReader = $httpLogReader;
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
     * @param HttpTransactionCollection $expectedHttpTransactions
     */
    public function testCreateAddSourcesCompileExecute(
        string $label,
        string $callbackUrl,
        string $manifestPath,
        array $sourcePaths,
        HttpTransactionCollection $expectedHttpTransactions
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
        self::assertSame(Job::STATE_EXECUTION_COMPLETE, $job->getState());

        $transactions = $this->httpLogReader->getTransactions();
        $this->httpLogReader->reset();

        self::assertCount(count($expectedHttpTransactions), $transactions);

        foreach ($expectedHttpTransactions as $transactionIndex => $expectedTransaction) {
            $transaction = $transactions->get($transactionIndex);
            self::assertInstanceOf(HttpTransaction::class, $transaction);

            $this->assertTransactionsAreEquivalent($expectedTransaction, $transaction, $transactionIndex);
        }
    }

    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback/1';

        return [
            'default' => [
                'label' => $label,
                'callbackUrl' => 'http://example.com/callback/1',
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'sourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedHttpTransactions' => $this->createHttpTransactionCollection([
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'test',
                            // @todo: fix in #268
                            'path' => '/app/source/Test/chrome-open-index.yml',
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://nginx/index.html',
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'step',
                            'name' => 'verify page is open',
                            'status' => 'passed',
                            'statements' => [
                                [
                                    'type' => 'assertion',
                                    'source' => '$page.url is "http://nginx/index.html"',
                                    'status' => 'passed',
                                ],
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'test',
                            'path' => '/app/source/Test/chrome-firefox-open-index.yml',
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://nginx/index.html',
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'step',
                            'name' => 'verify page is open',
                            'status' => 'passed',
                            'statements' => [
                                [
                                    'type' => 'assertion',
                                    'source' => '$page.url is "http://nginx/index.html"',
                                    'status' => 'passed',
                                ],
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'test',
                            'path' => '/app/source/Test/chrome-firefox-open-index.yml',
                            'config' => [
                                'browser' => 'firefox',
                                'url' => 'http://nginx/index.html',
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'step',
                            'name' => 'verify page is open',
                            'status' => 'passed',
                            'statements' => [
                                [
                                    'type' => 'assertion',
                                    'source' => '$page.url is "http://nginx/index.html"',
                                    'status' => 'passed',
                                ],
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'test',
                            'path' => '/app/source/Test/chrome-open-form.yml',
                            'config' => [
                                'browser' => 'chrome',
                                'url' => 'http://nginx/form.html',
                            ],
                        ]),
                        new Response()
                    ),
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'step',
                            'name' => 'verify page is open',
                            'status' => 'passed',
                            'statements' => [
                                [
                                    'type' => 'assertion',
                                    'source' => '$page.url is "http://nginx/form.html"',
                                    'status' => 'passed',
                                ],
                            ],
                        ]),
                        new Response()
                    ),
                ]),
            ],
        ];
    }

    private function assertTransactionsAreEquivalent(
        HttpTransaction $expected,
        HttpTransaction $actual,
        int $transactionIndex
    ): void {
        $this->assertRequestsAreEquivalent($expected->getRequest(), $actual->getRequest(), $transactionIndex);

        $expectedResponse = $expected->getResponse();
        $actualResponse = $actual->getResponse();

        if (null === $expectedResponse) {
            self::assertNull(
                $actualResponse,
                'Response at index ' . (string) $transactionIndex . 'expected to be null'
            );
        }

        if ($expectedResponse instanceof ResponseInterface) {
            self::assertInstanceOf(ResponseInterface::class, $actualResponse);
            $this->assertResponsesAreEquivalent($expectedResponse, $actualResponse, $transactionIndex);
        }
    }

    private function assertRequestsAreEquivalent(
        RequestInterface $expected,
        RequestInterface $actual,
        int $transactionIndex
    ): void {
        self::assertSame(
            $expected->getMethod(),
            $actual->getMethod(),
            'Method of request at index ' . $transactionIndex . ' not as expected'
        );

        self::assertSame(
            (string) $expected->getUri(),
            (string) $actual->getUri(),
            'URL of request at index ' . $transactionIndex . ' not as expected'
        );

        self::assertSame(
            $expected->getHeaderLine('content-type'),
            $actual->getHeaderLine('content-type'),
            'Content-type header of request at index ' . $transactionIndex . ' not as expected'
        );

        self::assertSame(
            json_decode($expected->getBody()->getContents(), true),
            json_decode($actual->getBody()->getContents(), true),
            'Body of request at index ' . $transactionIndex . ' not as expected'
        );
    }

    private function assertResponsesAreEquivalent(
        ResponseInterface $expected,
        ResponseInterface $actual,
        int $transactionIndex
    ): void {
        self::assertSame(
            $expected->getStatusCode(),
            $actual->getStatusCode(),
            'Status code of response at index ' . $transactionIndex . ' not as expected'
        );
    }

    private function initializeSourceStore(): void
    {
        $sourceStoreInitializer = self::$container->get(SourceStoreInitializer::class);
        self::assertInstanceOf(SourceStoreInitializer::class, $sourceStoreInitializer);
        if ($sourceStoreInitializer instanceof SourceStoreInitializer) {
            $sourceStoreInitializer->initialize();
        }
    }

    /**
     * @param HttpTransaction[] $transactions
     *
     * @return HttpTransactionCollection
     */
    private function createHttpTransactionCollection(array $transactions): HttpTransactionCollection
    {
        $collection = new HttpTransactionCollection();
        foreach ($transactions as $transaction) {
            if ($transaction instanceof HttpTransaction) {
                $collection->add($transaction);
            }
        }

        return $collection;
    }

    private function createHttpTransaction(RequestInterface $request, ResponseInterface $response): HttpTransaction
    {
        return new HttpTransaction($request, $response, null, []);
    }

    /**
     * @param string $label
     * @param string $callbackUrl
     * @param array<mixed> $payload
     *
     * @return RequestInterface
     */
    private function createExpectedRequest(string $label, string $callbackUrl, array $payload): RequestInterface
    {
        return new Request(
            'POST',
            $callbackUrl,
            [
                'content-type' => 'application/json',
            ],
            (string) json_encode([
                'label' => $label,
                'type' => 'execute-document-received',
                'payload' => $payload,
            ])
        );
    }
}
