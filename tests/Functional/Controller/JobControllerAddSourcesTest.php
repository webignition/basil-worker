<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Job;
use App\Event\SourcesAddedEvent;
use App\Request\AddSourcesRequest;
use App\Services\JobStore;
use App\Services\SourceStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\SourcesAddedEventSubscriber;
use App\Tests\Services\SourceStoreInitializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class JobControllerAddSourcesTest extends AbstractBaseFunctionalTest
{
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

    protected function setUp(): void
    {
        parent::setUp();

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);

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

        $sourcesAddedEventSubscriber = self::$container->get(SourcesAddedEventSubscriber::class);
        self::assertInstanceOf(SourcesAddedEventSubscriber::class, $sourcesAddedEventSubscriber);
        if ($sourcesAddedEventSubscriber instanceof SourcesAddedEventSubscriber) {
            $this->sourcesAddedEventSubscriber = $sourcesAddedEventSubscriber;
        }

        $this->initializeSourceStore();

        $this->job = $jobStore->create(md5('label content'), 'http://example.com/callback');
        self::assertSame([], $this->job->getSources());
        self::assertNull($this->sourcesAddedEventSubscriber->getEvent());

        $this->response = $this->getAddSourcesResponse();
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

    private function getAddSourcesResponse(): Response
    {
        $requestSources = [];
        foreach (self::EXPECTED_SOURCES as $source) {
            $requestSources[$source] = $this->basilFixtureHandler->createUploadedFile($source);
        }

        $requestFiles = array_merge(
            [
                AddSourcesRequest::KEY_MANIFEST => new UploadedFile(
                    getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                    'manifest.yml',
                    'text/yaml',
                    null,
                    true
                ),
            ],
            $requestSources
        );

        $this->client->request(
            'POST',
            '/add-sources',
            [],
            $requestFiles
        );

        return $this->client->getResponse();
    }
}
