<?php

declare(strict_types=1);

namespace App\Tests\Integration\EndToEnd;

use App\Entity\Job;
use App\Entity\Test;
use App\Entity\TestConfiguration;
use App\Services\JobStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class CreateAddSourcesCompileExecuteTest extends AbstractBaseFunctionalTest
{
    private ClientRequestSender $clientRequestSender;
    private JobStore $jobStore;
    private UploadedFileFactory $uploadedFileFactory;
    private BasilFixtureHandler $basilFixtureHandler;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRequestSender = self::$container->get(ClientRequestSender::class);
        self::assertInstanceOf(ClientRequestSender::class, $clientRequestSender);
        if ($clientRequestSender instanceof ClientRequestSender) {
            $this->clientRequestSender = $clientRequestSender;
        }

        $jobStore = self::$container->get(JobStore::class);
        self::assertInstanceOf(JobStore::class, $jobStore);
        if ($jobStore instanceof JobStore) {
            $this->jobStore = $jobStore;
        }

        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        self::assertInstanceOf(UploadedFileFactory::class, $uploadedFileFactory);
        if ($uploadedFileFactory instanceof UploadedFileFactory) {
            $this->uploadedFileFactory = $uploadedFileFactory;
        }

        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        self::assertInstanceOf(BasilFixtureHandler::class, $basilFixtureHandler);
        if ($basilFixtureHandler instanceof BasilFixtureHandler) {
            $this->basilFixtureHandler = $basilFixtureHandler;
        }

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $this->removeAllEntities(Job::class);
        $this->removeAllEntities(Test::class);
        $this->removeAllEntities(TestConfiguration::class);

        $this->initializeSourceStore();
    }

    public function testCreateAddSourcesCompileExecute()
    {
        $label = md5('label content');
        $callbackUrl = 'http://example.com/callback';

        $sources = [
            'Test/chrome-open-index.yml',
            'Test/chrome-firefox-open-index.yml',
            'Test/chrome-open-form.yml',
        ];

        $manifestPath = getcwd() . '/tests/Fixtures/Manifest/manifest.txt';

        $createJobResponse = $this->clientRequestSender->createJob($label, $callbackUrl);
        self::assertSame(200, $createJobResponse->getStatusCode());
        self::assertTrue($this->jobStore->hasJob());

        $job = $this->jobStore->getJob();
        self::assertSame(Job::STATE_COMPILATION_AWAITING, $job->getState());

        $addJobSourcesResponse = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest($manifestPath),
            $this->basilFixtureHandler->createUploadFileCollection($sources)
        );
        self::assertSame(200, $addJobSourcesResponse->getStatusCode());

        $job = $this->jobStore->getJob();
        self::assertSame($sources, $job->getSources());

        // @todo: verify execution in #223
    }

    private function initializeSourceStore(): void
    {
        $sourceStoreInitializer = self::$container->get(SourceStoreInitializer::class);
        self::assertInstanceOf(SourceStoreInitializer::class, $sourceStoreInitializer);
        if ($sourceStoreInitializer instanceof SourceStoreInitializer) {
            $sourceStoreInitializer->initialize();
        }
    }

    /**
     * @param class-string $entityClassName
     */
    private function removeAllEntities(string $entityClassName): void
    {
        $repository = $this->entityManager->getRepository($entityClassName);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAllEntities(Job::class);
        $this->removeAllEntities(Test::class);
        $this->removeAllEntities(TestConfiguration::class);
    }
}
