<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EnvironmentSetup;
use App\Tests\Model\JobSetup;
use App\Tests\Model\SourceSetup;
use App\Tests\Model\TestSetup;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\EnvironmentFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    private JobStore $jobStore;
    private ClientRequestSender $clientRequestSender;
    private EnvironmentFactory $environmentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        \assert($jobStore instanceof JobStore);
        $this->jobStore = $jobStore;

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        \assert($clientRequestSender instanceof ClientRequestSender);
        $this->clientRequestSender = $clientRequestSender;

        $environmentFactory = self::$container->get(EnvironmentFactory::class);
        \assert($environmentFactory instanceof EnvironmentFactory);
        $this->environmentFactory = $environmentFactory;
    }

    public function testCreate(): void
    {
        self::assertFalse($this->jobStore->has());

        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';
        $maximumDurationInSeconds = 600;

        $response = $this->clientRequestSender->createJob($label, $callbackUrl, $maximumDurationInSeconds);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        self::assertTrue($this->jobStore->has());
        self::assertEquals(
            Job::create($label, $callbackUrl, $maximumDurationInSeconds),
            $this->jobStore->get()
        );
    }

    public function testStatusNoJob(): void
    {
        $this->client->request('GET', '/status');

        $response = $this->client->getResponse();

        $expectedResponse = new JsonResponse([], 400);

        self::assertSame(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode()
        );

        self::assertJsonStringEqualsJsonString(
            (string) $expectedResponse->getContent(),
            (string) $response->getContent()
        );
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(EnvironmentSetup $setup, JsonResponse $expectedResponse): void
    {
        $this->environmentFactory->create($setup);

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

    /**
     * @return array[]
     */
    public function statusDataProvider(): array
    {
        return [
            'new job, no sources, no tests' => [
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())
                            ->withLabel('label content')
                            ->withCallbackUrl('http://example.com/callback')
                            ->withMaximumDurationInSeconds(10)
                    ),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())
                            ->withLabel('label content')
                            ->withCallbackUrl('http://example.com/callback')
                            ->withMaximumDurationInSeconds(11)
                    )->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                        (new SourceSetup())->withPath('Test/test3.yml'),
                    ]),
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
                'setup' => (new EnvironmentSetup())
                    ->withJobSetup(
                        (new JobSetup())
                            ->withLabel('label content')
                            ->withCallbackUrl('http://example.com/callback')
                            ->withMaximumDurationInSeconds(12)
                    )->withSourceSetups([
                        (new SourceSetup())->withPath('Test/test1.yml'),
                        (new SourceSetup())->withPath('Test/test2.yml'),
                        (new SourceSetup())->withPath('Test/test3.yml'),
                    ])->withTestSetups([
                        (new TestSetup())
                            ->withSource('var/basil/source/Test/test1.yml')
                            ->withTarget('var/basil/tests/GeneratedTest1.php')
                            ->withStepCount(3),
                        (new TestSetup())
                            ->withSource('var/basil/source/Test/test2.yml')
                            ->withTarget('var/basil/tests/GeneratedTest2.php')
                            ->withStepCount(2),
                    ]),
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
                                'target' => 'GeneratedTest1.php',
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
                                'target' => 'GeneratedTest2.php',
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
