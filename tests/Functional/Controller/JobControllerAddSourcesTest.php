<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Message\JobReadyMessage;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\Asserter\SourceEntityAsserter;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourceFileStoreHandler;
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

    protected function setUp(): void
    {
        parent::setUp();

        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreHandler::class);
        \assert($sourceFileStoreInitializer instanceof SourceFileStoreHandler);
        $sourceFileStoreInitializer->clear();

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

        $this->response = $clientRequestSender->addJobSources(
            $uploadedFileFactory->createForManifest(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
            $basilFixtureHandler->createUploadFileCollection(self::EXPECTED_SOURCES)
        );
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
