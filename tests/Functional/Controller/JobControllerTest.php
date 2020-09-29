<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Entity\TestConfiguration;
use App\Request\AddSourcesRequest;
use App\Request\JobCreateRequest;
use App\Services\JobStore;
use App\Services\SourceStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\SourceStoreInitializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;
    private BasilFixtureHandler $basilFixtureHandler;
    private SourceStore $sourceStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }

        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        self::assertInstanceOf(BasilFixtureHandler::class, $basilFixtureHandler);
        if ($basilFixtureHandler instanceof BasilFixtureHandler) {
            $this->basilFixtureHandler = $basilFixtureHandler;
        }

        $sourceStore = self::$container->get(SourceStore::class);
        self::assertInstanceOf(SourceStore::class, $sourceStore);
        if ($sourceStore instanceof SourceStore) {
            $this->sourceStore = $sourceStore;
        }
    }

    public function testCreate()
    {
        self::assertNull($this->jobStore->retrieve());

        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';

        $this->client->request('POST', '/create', [
            JobCreateRequest::KEY_LABEL => $label,
            JobCreateRequest::KEY_CALLBACK_URL => $callbackUrl,
        ]);

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        self::assertNotNull($this->jobStore->retrieve());
        self::assertEquals(
            Job::create($label, $callbackUrl),
            $this->jobStore->retrieve()
        );
    }

    public function testAddSources()
    {
        $sourceStoreInitializer = self::$container->get(SourceStoreInitializer::class);
        self::assertInstanceOf(SourceStoreInitializer::class, $sourceStoreInitializer);
        if ($sourceStoreInitializer instanceof SourceStoreInitializer) {
            $sourceStoreInitializer->initialize();
        }

        $job = Job::create(md5('label content'), 'http://example.com/callback');
        $this->jobStore->store($job);
        self::assertSame([], $job->getSources());

        $this->client->request(
            'POST',
            '/add-sources',
            [],
            [
                AddSourcesRequest::KEY_MANIFEST => new UploadedFile(
                    getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                    'manifest.yml',
                    'text/yaml',
                    null,
                    true
                ),
                'Test/test1.yml' => $this->basilFixtureHandler->createUploadedFile('Test/test1.yml'),
                'Test/test2.yml' => $this->basilFixtureHandler->createUploadedFile('Test/test2.yml'),
                'Test/test3.yml' => $this->basilFixtureHandler->createUploadedFile('Test/test3.yml'),
            ]
        );

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        $expectedSources = [
            'Test/test1.yml',
            'Test/test2.yml',
            'Test/test3.yml',
        ];

        self::assertSame($expectedSources, $job->getSources());

        $retrievedJob = $this->jobStore->retrieve();
        if ($retrievedJob instanceof Job) {
            self::assertEquals($expectedSources, $retrievedJob->getSources());
        }

        foreach ($expectedSources as $expectedSource) {
            self::assertTrue($this->sourceStore->has($expectedSource));
        }
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
                    $job = Job::create('label content', 'http://example.com/callback');
                    $jobStore->store($job);
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
                    $job = Job::create('label content', 'http://example.com/callback');
                    $jobStore->store($job);

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
                    $job = Job::create('label content', 'http://example.com/callback');
                    $jobStore->store($job);

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
