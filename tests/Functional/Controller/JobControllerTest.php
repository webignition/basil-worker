<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Entity\TestConfiguration;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\TestTestFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStore $jobStore;
    private ClientRequestSender $clientRequestSender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testCreate()
    {
        self::assertFalse($this->jobStore->hasJob());

        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';
        $maximumDurationInSeconds = 600;

        $response = $this->clientRequestSender->createJob($label, $callbackUrl, $maximumDurationInSeconds);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        self::assertTrue($this->jobStore->hasJob());
        self::assertEquals(
            Job::create($label, $callbackUrl, $maximumDurationInSeconds),
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

        $testFactory = self::$container->get(TestTestFactory::class);
        self::assertInstanceOf(TestTestFactory::class, $testFactory);

        $initializer($jobStore, $testFactory);

        $this->client->request('GET', '/status');

        $response = $this->client->getResponse();

        self::assertSame(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode()
        );

        self::assertJsonStringEqualsJsonString(
            (string) $expectedResponse->getContent(),
            (string) $response->getContent()
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
                    $jobStore->create('label content', 'http://example.com/callback', 10);
                },
                'expectedResponse' => new JsonResponse(
                    [
                        'label' => 'label content',
                        'callback_url' => 'http://example.com/callback',
                        'maximum_duration_in_seconds' => 10,
                        'sources' => [],
                        'compilation_state' => 'awaiting',
                        'execution_state' => 'awaiting',
                        'tests' => [],
                    ]
                ),
            ],
            'new job, has sources, no tests' => [
                'initializer' => function (JobStore $jobStore) {
                    $job = $jobStore->create('label content', 'http://example.com/callback', 11);

                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store($job);
                },
                'expectedResponse' => new JsonResponse(
                    [
                        'label' => 'label content',
                        'callback_url' => 'http://example.com/callback',
                        'maximum_duration_in_seconds' => 11,
                        'sources' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                            'Test/test3.yml',
                        ],
                        'compilation_state' => 'running',
                        'execution_state' => 'awaiting',
                        'tests' => [],
                    ]
                ),
            ],
            'new job, has sources, has tests, compilation not complete' => [
                'initializer' => function (JobStore $jobStore, TestTestFactory $testFactory) {
                    $job = $jobStore->create('label content', 'http://example.com/callback', 12);

                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store($job);

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        'generated/GeneratedTest1.php',
                        3
                    );

                    $testFactory->create(
                        TestConfiguration::create('chrome', 'http://example.com'),
                        'Test/test2.yml',
                        'generated/GeneratedTest2.php',
                        2
                    );
                },
                'expectedResponse' => new JsonResponse(
                    [
                        'label' => 'label content',
                        'callback_url' => 'http://example.com/callback',
                        'maximum_duration_in_seconds' => 12,
                        'sources' => [
                            'Test/test1.yml',
                            'Test/test2.yml',
                            'Test/test3.yml',
                        ],
                        'compilation_state' => 'running',
                        'execution_state' => 'awaiting',
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
