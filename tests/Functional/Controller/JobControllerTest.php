<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Entity\TestConfiguration;
use App\Services\JobStore;
use App\Services\TestStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\ClientRequestSender;
use Symfony\Component\HttpFoundation\JsonResponse;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;
    private ClientRequestSender $clientRequestSender;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        self::assertInstanceOf(ClientRequestSender::class, $clientRequestSender);
        if ($clientRequestSender instanceof ClientRequestSender) {
            $this->clientRequestSender = $clientRequestSender;
        }
    }

    public function testCreate()
    {
        self::assertFalse($this->jobStore->hasJob());

        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';

        $response = $this->clientRequestSender->createJob($label, $callbackUrl);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        self::assertTrue($this->jobStore->hasJob());
        self::assertEquals(
            Job::create($label, $callbackUrl),
            $this->jobStore->getJob()
        );
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(callable $initializer, JsonResponse $expectedResponse)
    {
        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);

        $testStore = self::$container->get(TestStore::class);
        self::assertInstanceOf(TestStore::class, $testStore);

        $initializer($jobStore, $testStore);

        $this->client->request('GET', '/status');

        $response = $this->client->getResponse();

        self::assertSame(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode()
        );

        self::assertSame(
            json_decode((string) $expectedResponse->getContent(), true),
            json_decode((string) $response->getContent(), true)
        );
    }

    public function statusDataProvider(): array
    {
        return [
            'no job' => [
                'initializer' => function () {
                },
                'expectedResponse' => new JsonResponse([], 400),
            ],
            'new job, no sources, no tests' => [
                'initializer' => function (JobStore $jobStore) {
                    $jobStore->create('label content', 'http://example.com/callback');
                },
                'expectedResponse' => new JsonResponse(
                    [
                        'state' => 'compilation-awaiting',
                        'label' => 'label content',
                        'callback_url' => 'http://example.com/callback',
                        'sources' => [],
                        'tests' => [],
                    ]
                ),
            ],
            'new job, has sources, no tests' => [
                'initializer' => function (JobStore $jobStore) {
                    $job = $jobStore->create('label content', 'http://example.com/callback');

                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store($job);
                },
                'expectedResponse' => new JsonResponse(
                    [
                        'state' => 'compilation-awaiting',
                        'label' => 'label content',
                        'callback_url' => 'http://example.com/callback',
                        'sources' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                            'Test/test3.yml',
                        ],
                        'tests' => [],
                    ]
                ),
            ],
            'new job, has sources, has tests' => [
                'initializer' => function (JobStore $jobStore, TestStore $testStore) {
                    $job = $jobStore->create('label content', 'http://example.com/callback');

                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store($job);

                    $testStore->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        'generated/GeneratedTest1.php',
                        3
                    );

                    $testStore->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        'Test/test2.yml',
                        'generated/GeneratedTest2.php',
                        2
                    );
                },
                'expectedResponse' => new JsonResponse(
                    [
                        'state' => 'compilation-awaiting',
                        'label' => 'label content',
                        'callback_url' => 'http://example.com/callback',
                        'sources' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                            'Test/test3.yml',
                        ],
                        'tests' => [
                            [
                                'configuration' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://example.com',
                                ],
                                'source' => 'Test/test1.yml',
                                'target' => 'generated/GeneratedTest1.php',
                                'step_count' => 3,
                                'state' => 'awaiting',
                                'position' => 1,
                            ],
                            [
                                'configuration' => [
                                    'browser' => 'chrome',
                                    'url' => 'http://example.com',
                                ],
                                'source' => 'Test/test2.yml',
                                'target' => 'generated/GeneratedTest2.php',
                                'step_count' => 2,
                                'state' => 'awaiting',
                                'position' => 2,
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }
}
