<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Event\SourcesAddedEvent;
use App\Services\JobStore;
use App\Services\SourceStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourcesAddedEventSubscriber;
use App\Tests\Services\SourceStoreInitializer;
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
    private SourceStore $sourceStore;
    private SourcesAddedEventSubscriber $sourcesAddedEventSubscriber;
    private Job $job;
    private Response $response;
    private ClientRequestSender $clientRequestSender;
    private UploadedFileFactory $uploadedFileFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
        $this->initializeSourceStore();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);

        $this->job = $jobStore->create(md5('label content'), 'http://example.com/callback');
        self::assertSame([], $this->job->getSources());
        self::assertNull($this->sourcesAddedEventSubscriber->getEvent());

        $this->response = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
            $this->basilFixtureHandler->createUploadFileCollection(self::EXPECTED_SOURCES)
        );
    }

    public function testResponse()
    {
        self::assertSame(200, $this->response->getStatusCode());
        self::assertSame('application/json', $this->response->headers->get('content-type'));
        self::assertSame('{}', $this->response->getContent());
    }

    public function testJobHasSources()
    {
        self::assertSame(self::EXPECTED_SOURCES, $this->job->getSources());
    }

    public function testSourcesAreStored()
    {
        foreach (self::EXPECTED_SOURCES as $expectedSource) {
            self::assertTrue($this->sourceStore->has($expectedSource));
        }
    }

    public function testSourcesAddedEventIsDispatched()
    {
        self::assertEquals(
            new SourcesAddedEvent(),
            $this->sourcesAddedEventSubscriber->getEvent()
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
}
