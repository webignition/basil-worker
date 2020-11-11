<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous\EndToEnd;

use App\Entity\Job;
use App\Tests\Integration\AbstractEndToEndTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\JobConfiguration;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\Integration\HttpLogReader;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Transaction\HttpTransaction;

class CreateAddSourcesCompileExecuteTest extends AbstractEndToEndTest
{
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
        InvokableInterface $postAssertions
    ) {
        $this->doCreateJobAddSourcesTest(
            $jobConfiguration,
            $expectedSourcePaths,
            Invokable::createEmpty(),
            Job::STATE_EXECUTION_COMPLETE,
            $postAssertions
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
                'postAssertions' => new Invokable(
                    function (HttpTransactionCollection $expectedHttpTransactions, HttpLogReader $httpLogReader) {
                        $transactions = $httpLogReader->getTransactions();
                        $httpLogReader->reset();

                        self::assertCount(count($expectedHttpTransactions), $transactions);
                        $this->assertTransactionCollectionsAreEquivalent($expectedHttpTransactions, $transactions);
                    },
                    [
                        $this->createHttpTransactionCollection([
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
                        new ServiceReference(HttpLogReader::class),
                    ]
                ),
            ],
            'step failed' => [
                'jobConfiguration' => new JobConfiguration(
                    $label,
                    $callbackUrl,
                    getcwd() . '/tests/Fixtures/Manifest/manifest-step-failure.txt'
                ),
                'expectedSourcePaths' => [
                    'Test/chrome-open-index-with-step-failure.yml',
                ],
                'postAssertions' => new Invokable(
                    function (HttpTransactionCollection $expectedHttpTransactions, HttpLogReader $httpLogReader) {
                        $transactions = $httpLogReader->getTransactions();
                        $httpLogReader->reset();

                        self::assertCount(count($expectedHttpTransactions), $transactions);
                        $this->assertTransactionCollectionsAreEquivalent($expectedHttpTransactions, $transactions);
                    },
                    [
                        $this->createHttpTransactionCollection([
                            $this->createHttpTransaction(
                                $this->createExpectedRequest($label, $callbackUrl, [
                                    'type' => 'test',
                                    'path' => 'Test/chrome-open-index-with-step-failure.yml',
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
                                ]),
                                new Response()
                            ),
                        ]),
                        new ServiceReference(HttpLogReader::class),
                    ]
                ),
            ],
        ];
    }

    private function assertTransactionCollectionsAreEquivalent(
        HttpTransactionCollection $expectedHttpTransactions,
        HttpTransactionCollection $transactions
    ): void {
        foreach ($expectedHttpTransactions as $transactionIndex => $expectedTransaction) {
            $transaction = $transactions->get($transactionIndex);
            self::assertInstanceOf(HttpTransaction::class, $transaction);

            $this->assertTransactionsAreEquivalent($expectedTransaction, $transaction, $transactionIndex);
        }
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
