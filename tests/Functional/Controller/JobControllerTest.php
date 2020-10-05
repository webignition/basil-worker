<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Request\JobCreateRequest;
use App\Services\JobStore;
use App\Services\ManifestStore;
use App\Services\TestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Symfony\Component\HttpFoundation\JsonResponse;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }
    }

    public function testCreate()
    {
        self::assertFalse($this->jobStore->hasJob());

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

        $manifestStore = self::$container->get(ManifestStore::class);
        self::assertInstanceOf(ManifestStore::class, $manifestStore);

        $initializer($jobStore, $testStore, $manifestStore);

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
                    $jobStore->store();
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
                'initializer' => function (JobStore $jobStore, TestStore $testStore, ManifestStore $manifestStore) {
                    $job = $jobStore->create('label content', 'http://example.com/callback');

                    $job->setSources([
                        'Test/test1.yml',
                        'Test/test2.yml',
                        'Test/test3.yml',
                    ]);
                    $jobStore->store();

                    $manifest1 = new TestManifest(
                        new Configuration('chrome', 'http://example.com'),
                        'Test/test1.yml',
                        'generated/GeneratedTest1.php',
                        3
                    );

                    $manifest2 = new TestManifest(
                        new Configuration('chrome', 'http://example.com'),
                        'Test/test2.yml',
                        'generated/GeneratedTest2.php',
                        2
                    );

                    $manifestPath1 = $manifestStore->store($manifest1);
                    $manifestPath2 = $manifestStore->store($manifest2);

                    $testStore->create('Test/test1.yml', $manifest1, $manifestPath1);
                    $testStore->create('Test/test2.yml', $manifest2, $manifestPath2);
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
