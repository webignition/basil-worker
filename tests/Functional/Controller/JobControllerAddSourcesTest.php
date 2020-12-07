<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Event\SourcesAddedEvent;
use App\Services\JobStore;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\InvokableFactory\SourceGetterFactory;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use App\Tests\Services\SourcesAddedEventSubscriber;
use App\Tests\Services\UploadedFileFactory;
use Symfony\Component\HttpFoundation\Response;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class JobControllerAddSourcesTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private const EXPECTED_SOURCES = [
        'Test/chrome-open-index.yml',
        'Test/chrome-firefox-open-index.yml',
        'Test/chrome-open-form.yml',
    ];

    private BasilFixtureHandler $basilFixtureHandler;
    private SourceFileStore $sourceFileStore;
    private SourcesAddedEventSubscriber $sourcesAddedEventSubscriber;
    private Job $job;
    private Response $response;
    private ClientRequestSender $clientRequestSender;
    private UploadedFileFactory $uploadedFileFactory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
        $this->initializeSourceFileStore();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);

        $this->job = $jobStore->create(md5('label content'), 'http://example.com/callback', 10);

        self::assertSame([], $this->invokableHandler->invoke(SourceGetterFactory::getAll()));
        self::assertNull($this->sourcesAddedEventSubscriber->getEvent());

        $this->response = $this->invokableHandler->invoke(new Invokable(
            function (ClientRequestSender $clientRequestSender) {
                return $clientRequestSender->addJobSources(
                    $this->uploadedFileFactory->createForManifest(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
                    $this->basilFixtureHandler->createUploadFileCollection(self::EXPECTED_SOURCES)
                );
            },
            [
                new ServiceReference(ClientRequestSender::class),
            ]
        ));
    }

    public function testResponse()
    {
        self::assertSame(200, $this->response->getStatusCode());
        self::assertSame('application/json', $this->response->headers->get('content-type'));
        self::assertSame('{}', $this->response->getContent());
    }

    public function testSourcesAreCreated()
    {
        self::assertSame(
            self::EXPECTED_SOURCES,
            $this->invokableHandler->invoke(SourceGetterFactory::getAllRelativePaths())
        );
    }

    public function testSourcesAreStored()
    {
        foreach (self::EXPECTED_SOURCES as $expectedSource) {
            self::assertTrue($this->sourceFileStore->has($expectedSource));
        }
    }

    public function testSourcesAddedEventIsDispatched()
    {
        self::assertEquals(
            new SourcesAddedEvent(),
            $this->sourcesAddedEventSubscriber->getEvent()
        );
    }

    private function initializeSourceFileStore(): void
    {
        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreInitializer::class);
        self::assertInstanceOf(SourceFileStoreInitializer::class, $sourceFileStoreInitializer);
        if ($sourceFileStoreInitializer instanceof SourceFileStoreInitializer) {
            $sourceFileStoreInitializer->initialize();
        }
    }
}
