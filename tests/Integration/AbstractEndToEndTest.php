<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Job;
use App\Services\JobStore;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\ClientRequestSender;
use App\Tests\Services\Integration\HttpLogReader;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpCache\ResponseCacheStrategyInterface;
use webignition\HttpHistoryContainer\Collection\HttpTransactionCollection;
use webignition\HttpHistoryContainer\Transaction\HttpTransaction;

abstract class AbstractEndToEndTest extends AbstractBaseIntegrationTest
{
    protected ClientRequestSender $clientRequestSender;
    protected JobStore $jobStore;
    private UploadedFileFactory $uploadedFileFactory;
    private BasilFixtureHandler $basilFixtureHandler;

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

        $this->initializeSourceStore();
    }

    protected function createJob(string $label, string $callbackUrl): JsonResponse
    {
        $response = $this->clientRequestSender->createJob($label, $callbackUrl);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->jobStore->hasJob());

        return $response;
    }

    protected function addJobSources(string $manifestPath, array $sourcePaths): JsonResponse
    {
        $response = $this->clientRequestSender->addJobSources(
            $this->uploadedFileFactory->createForManifest($manifestPath),
            $this->basilFixtureHandler->createUploadFileCollection($sourcePaths)
        );

        self::assertSame(200, $response->getStatusCode());

        return $response;
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
