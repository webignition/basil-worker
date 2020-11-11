<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\EndToEnd;

use App\Entity\Job;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\JobConfiguration;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\SourceStoreInitializer;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Transaction\HttpTransaction;

class CreateAddSourcesCompileExecuteTest extends AbstractEndToEndTest
{
    private HttpLogReader $httpLogReader;

    protected function setUp(): void
    {
        parent::setUp();

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
     * @param JobConfiguration $jobConfiguration
     * @param string[] $expectedSourcePaths
     * @param HttpTransactionCollection $expectedHttpTransactions
     */
    public function testCreateAddSourcesCompileExecute(
        JobConfiguration $jobConfiguration,
        array $expectedSourcePaths,
        HttpTransactionCollection $expectedHttpTransactions
    ) {
        $this->doCreateJobAddSourcesTest(
            $jobConfiguration,
            $expectedSourcePaths,
            Invokable::createEmpty(),
            Job::STATE_EXECUTION_COMPLETE,
            new Invokable(
                function (HttpTransactionCollection $expectedHttpTransactions) {
                    $transactions = $this->httpLogReader->getTransactions();
                    $this->httpLogReader->reset();

                    self::assertCount(count($expectedHttpTransactions), $transactions);

                    foreach ($expectedHttpTransactions as $transactionIndex => $expectedTransaction) {
                        $transaction = $transactions->get($transactionIndex);
                        self::assertInstanceOf(HttpTransaction::class, $transaction);

                        $this->assertTransactionsAreEquivalent($expectedTransaction, $transaction, $transactionIndex);
                    }
                },
                [
                    $expectedHttpTransactions,
                ]
            )
        );
    }

    public function createAddSourcesCompileExecuteDataProvider(): array
    {
        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback/1';

        return [
            'default' => [
                'jobConfiguration' => new JobConfiguration(
                    $label,
                    $callbackUrl,
                    getcwd() . '/tests/Fixtures/Manifest/manifest.txt'
                ),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedHttpTransactions' => $this->createHttpTransactionCollection([
                    $this->createHttpTransaction(
                        $this->createExpectedRequest($label, $callbackUrl, [
                            'type' => 'test',
                            'path' => 'Test/chrome-open-index.yml',
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
                            'path' => 'Test/chrome-firefox-open-index.yml',
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
                            'path' => 'Test/chrome-firefox-open-index.yml',
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
                            'path' => 'Test/chrome-open-form.yml',
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
