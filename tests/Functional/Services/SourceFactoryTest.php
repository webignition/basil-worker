<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\Manifest;
use App\Model\UploadedFileKey;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;
use App\Services\SourceFactory;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\FileStoreHandler;
use App\Tests\Services\UploadedFileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use App\Entity\Source;

class SourceFactoryTest extends AbstractBaseFunctionalTest
{
    private SourceFactory $factory;
    private SourceFileStore $sourceFileStore;
    private FileStoreHandler $localSourceStoreHandler;
    private FileStoreHandler $uploadStoreHandler;
    private UploadedFileFactory $uploadedFileFactory;

    /**
     * @var ObjectRepository<Source>
     */
    private ObjectRepository $sourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(SourceFactory::class);
        \assert($factory instanceof SourceFactory);
        $this->factory = $factory;

        $store = self::$container->get(SourceFileStore::class);
        \assert($store instanceof SourceFileStore);
        $this->sourceFileStore = $store;

        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        \assert($uploadedFileFactory instanceof UploadedFileFactory);
        $this->uploadedFileFactory = $uploadedFileFactory;

        $localSourceStoreHandler = self::$container->get('app.tests.services.file_store_handler.local_source');
        \assert($localSourceStoreHandler instanceof FileStoreHandler);
        $this->localSourceStoreHandler = $localSourceStoreHandler;
        $this->localSourceStoreHandler->clear();

        $uploadStoreHandler = self::$container->get('app.tests.services.file_store_handler.uploaded');
        \assert($uploadStoreHandler instanceof FileStoreHandler);
        $this->uploadStoreHandler = $uploadStoreHandler;
        $this->uploadStoreHandler->clear();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $sourceRepository = $entityManager->getRepository(Source::class);
        \assert($sourceRepository instanceof ObjectRepository);
        $this->sourceRepository = $sourceRepository;
    }

    protected function tearDown(): void
    {
        $this->localSourceStoreHandler->clear();
        $this->uploadStoreHandler->clear();

        parent::tearDown();
    }

    /**
     * @dataProvider createCollectionFromManifestDataProvider
     *
     * @param string[] $fixturePaths
     * @param string[] $expectedStoredTestPaths
     * @param Source[] $expectedSources
     */
    public function testCreateCollectionFromManifest(
        string $manifestPath,
        array $fixturePaths,
        array $expectedStoredTestPaths,
        array $expectedSources
    ): void {
        $manifestUploadedFile = $this->uploadedFileFactory->createForManifest($manifestPath);
        $uploadedSourceFiles = $this->uploadedFileFactory->createCollection(
            $this->uploadStoreHandler->copyFixtures($fixturePaths)
        );

        foreach ($uploadedSourceFiles as $encodedKey => $uploadedFile) {
            unset($uploadedSourceFiles[$encodedKey]);

            $key = UploadedFileKey::fromEncodedKey($encodedKey);
            $uploadedSourceFiles[(string) $key] = $uploadedFile;
        }

        $uploadedSources = new UploadedSourceCollection();
        foreach ($uploadedSourceFiles as $path => $uploadedFile) {
            $uploadedSources[] = new UploadedSource($path, $uploadedFile);
        }

        $manifest = new Manifest($manifestUploadedFile);

        self::assertCount(0, $this->sourceRepository->findAll());

        $this->factory->createCollectionFromManifest($manifest, $uploadedSources);
        foreach ($expectedStoredTestPaths as $expectedStoredTestPath) {
            self::assertTrue($this->sourceFileStore->has($expectedStoredTestPath));
        }

        $sources = $this->sourceRepository->findAll();
        self::assertCount(count($expectedSources), $sources);

        foreach ($sources as $sourceIndex => $source) {
            $expectedSource = $expectedSources[$sourceIndex];

            self::assertSame($expectedSource->getType(), $source->getType());
            self::assertSame($expectedSource->getPath(), $source->getPath());
        }
    }

    /**
     * @return array[]
     */
    public function createCollectionFromManifestDataProvider(): array
    {
        return [
            'empty manifest' => [
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/empty.txt',
                'fixturePaths' => [],
                'expectedStoredTestPaths' => [],
                'expectedSources' => [],
            ],
            'non-empty manifest' => [
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'fixturePaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedStoredTestPaths' => [
                    'Test/chrome-open-index.yml',
                    'Test/chrome-firefox-open-index.yml',
                    'Test/chrome-open-form.yml',
                ],
                'expectedSources' => [
                    Source::create(Source::TYPE_TEST, 'Test/chrome-open-index.yml'),
                    Source::create(Source::TYPE_TEST, 'Test/chrome-firefox-open-index.yml'),
                    Source::create(Source::TYPE_TEST, 'Test/chrome-open-form.yml'),
                ],
            ],
        ];
    }
}
