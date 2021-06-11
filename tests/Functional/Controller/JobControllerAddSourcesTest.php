<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Message\JobReadyMessage;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\Asserter\SourceEntityAsserter;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\FileStoreHandler;
use App\Tests\Services\UploadedFileFactory;
use Symfony\Component\HttpFoundation\Response;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\JobFactory;

class JobControllerAddSourcesTest extends AbstractBaseFunctionalTest
{
    private const EXPECTED_SOURCES = [
        'Test/chrome-open-index.yml',
        'Test/chrome-firefox-open-index.yml',
        'Test/chrome-open-form.yml',
        'Page/index.yml',
    ];

    private Response $response;
    private SourceEntityAsserter $sourceEntityAsserter;
    private MessengerAsserter $messengerAsserter;
    private FileStoreHandler $localSourceStoreHandler;
    private FileStoreHandler $uploadStoreHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $localSourceStoreHandler = self::$container->get('app.tests.services.file_store_handler.local_source');
        \assert($localSourceStoreHandler instanceof FileStoreHandler);
        $this->localSourceStoreHandler = $localSourceStoreHandler;
        $this->localSourceStoreHandler->clear();

        $uploadStoreHandler = self::$container->get('app.tests.services.file_store_handler.uploaded');
        \assert($uploadStoreHandler instanceof FileStoreHandler);
        $this->uploadStoreHandler = $uploadStoreHandler;
        $this->uploadStoreHandler->clear();

        $jobFactory = self::$container->get(JobFactory::class);
        self::assertInstanceOf(JobFactory::class, $jobFactory);
        $jobFactory->create(md5('label content'), 'http://example.com/callback', 10);

        $sourceEntityAsserter = self::$container->get(SourceEntityAsserter::class);
        \assert($sourceEntityAsserter instanceof SourceEntityAsserter);
        $this->sourceEntityAsserter = $sourceEntityAsserter;

        $this->sourceEntityAsserter->assertRepositoryIsEmpty();

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $this->messengerAsserter->assertQueueIsEmpty();

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        \assert($clientRequestSender instanceof ClientRequestSender);

        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        \assert($uploadedFileFactory instanceof UploadedFileFactory);

        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        \assert($basilFixtureHandler instanceof BasilFixtureHandler);

        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        \assert($uploadedFileFactory instanceof UploadedFileFactory);

        $uploadedFileCollection = $uploadedFileFactory->createCollection(
            $this->uploadStoreHandler->copyFixtures(self::EXPECTED_SOURCES)
        );

        $this->response = $clientRequestSender->addJobSources(
            $uploadedFileFactory->createForManifest(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
            $uploadedFileCollection
        );
    }

    protected function tearDown(): void
    {
        $this->localSourceStoreHandler->clear();
        $this->uploadStoreHandler->clear();

        parent::tearDown();
    }

    public function testResponse(): void
    {
        self::assertSame(200, $this->response->getStatusCode());
        self::assertSame('application/json', $this->response->headers->get('content-type'));
        self::assertSame('{}', $this->response->getContent());
    }

    public function testSourcesAreCreated(): void
    {
        $this->sourceEntityAsserter->assertRelativePathsEqual(self::EXPECTED_SOURCES);
    }

    public function testSourcesAreStored(): void
    {
        foreach (self::EXPECTED_SOURCES as $expectedSource) {
            $this->sourceEntityAsserter->assertSourceExists($expectedSource);
        }
    }

    public function testJobReadyEventIsDispatched(): void
    {
        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, new JobReadyMessage());
    }
}
