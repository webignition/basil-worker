<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\InvokableFactory\JobSetup;
use App\Tests\Services\InvokableFactory\JobSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\SourceSetup;
use App\Tests\Services\InvokableFactory\SourceSetupInvokableFactory;
use App\Tests\Services\InvokableFactory\TestSetup;
use App\Tests\Services\InvokableFactory\TestSetupInvokableFactory;
use App\Tests\Services\InvokableHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use webignition\BasilWorker\PersistenceBundle\Entity\Job;
use webignition\BasilWorker\PersistenceBundle\Services\Store\JobStore;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobControllerTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private JobStore $jobStore;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
    }

    public function testCreate()
    {
        self::assertFalse($this->jobStore->has());

        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';
        $maximumDurationInSeconds = 600;

        $response = $this->invokableHandler->invoke(new Invokable(
            function (
                ClientRequestSender $clientRequestSender,
                string $label,
                string $callbackUrl,
                int $maximumDurationInSeconds
            ) {
                return $clientRequestSender->createJob($label, $callbackUrl, $maximumDurationInSeconds);
            },
            [
                new ServiceReference(ClientRequestSender::class),
                $label,
                $callbackUrl,
                $maximumDurationInSeconds
            ]
        ));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame('{}', $response->getContent());

        self::assertTrue($this->jobStore->has());
        self::assertEquals(
            Job::create($label, $callbackUrl, $maximumDurationInSeconds),
            $this->jobStore->get()
        );
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(InvokableInterface $setup, JsonResponse $expectedResponse)
    {
        $this->invokableHandler->invoke($setup);

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
                'setup' => Invokable::createEmpty(),
                'expectedResponse' => new JsonResponse([], 400),
            ],
            'new job, no sources, no tests' => [
                'setup' => JobSetupInvokableFactory::setup(
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
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withLabel('label content')
                            ->withCallbackUrl('http://example.com/callback')
                            ->withMaximumDurationInSeconds(11)
                    ),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test3.yml'),
                    ]),
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
                'setup' => new InvokableCollection([
                    'create job' => JobSetupInvokableFactory::setup(
                        (new JobSetup())
                            ->withLabel('label content')
                            ->withCallbackUrl('http://example.com/callback')
                            ->withMaximumDurationInSeconds(12)
                    ),
                    'add job sources' => SourceSetupInvokableFactory::setupCollection([
                        (new SourceSetup())
                            ->withPath('Test/test1.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test2.yml'),
                        (new SourceSetup())
                            ->withPath('Test/test3.yml'),
                    ]),
                    'create tests' => TestSetupInvokableFactory::setupCollection([
                        (new TestSetup())
                            ->withSource('/app/source/Test/test1.yml')
                            ->withTarget('/app/tests/GeneratedTest1.php')
                            ->withStepCount(3),
                        (new TestSetup())
                            ->withSource('/app/source/Test/test2.yml')
                            ->withTarget('/app/tests/GeneratedTest2.php')
                            ->withStepCount(2),
                    ]),
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
