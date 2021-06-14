<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Message\JobReadyMessage;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\JsonResponseAsserter;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\Asserter\SourceEntityAsserter;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourceFileStoreInitializer;
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
    private JsonResponseAsserter $jsonResponseAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreInitializer::class);
        \assert($sourceFileStoreInitializer instanceof SourceFileStoreInitializer);
        $sourceFileStoreInitializer->initialize();

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

        $jsonResponseAsserter = self::$container->get(JsonResponseAsserter::class);
        \assert($jsonResponseAsserter instanceof JsonResponseAsserter);
        $this->jsonResponseAsserter = $jsonResponseAsserter;

        $this->response = $clientRequestSender->addJobSources(
            $uploadedFileFactory->createForManifest(getcwd() . '/tests/Fixtures/Manifest/manifest.txt'),
            $basilFixtureHandler->createUploadFileCollection(self::EXPECTED_SOURCES)
        );
    }

    public function testResponse(): void
    {
        $this->jsonResponseAsserter->assertJsonResponse(200, (object) [], $this->response);
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
