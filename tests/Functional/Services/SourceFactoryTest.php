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
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;

class SourceFactoryTest extends AbstractBaseFunctionalTest
{
    private SourceFactory $factory;
    private SourceFileStore $sourceFileStore;

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

        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreInitializer::class);
        \assert($sourceFileStoreInitializer instanceof SourceFileStoreInitializer);
        $sourceFileStoreInitializer->initialize();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $sourceRepository = $entityManager->getRepository(Source::class);
        \assert($sourceRepository instanceof ObjectRepository);
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @dataProvider createCollectionFromManifestDataProvider
     *
     * @param string[] $uploadedSourcePaths
     * @param string[] $expectedStoredTestPaths
     * @param Source[] $expectedSources
     */
    public function testCreateCollectionFromManifest(
        string $manifestPath,
        array $uploadedSourcePaths,
        array $expectedStoredTestPaths,
        array $expectedSources
    ): void {
        $manifestUploadedFile = $this->createUploadedFile($manifestPath);
        $uploadedSourceFiles = $this->createUploadedFileCollection($uploadedSourcePaths);

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
                'uploadedSourcePaths' => [],
                'expectedStoredTestPaths' => [],
                'expectedSources' => [],
            ],
            'non-empty manifest' => [
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/manifest.txt',
                'uploadedSourcePaths' => [
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

    private function createUploadedFile(string $manifestPath): UploadedFile
    {
        $uploadedFileFactory = self::$container->get(UploadedFileFactory::class);
        \assert($uploadedFileFactory instanceof UploadedFileFactory);

        return $uploadedFileFactory->createForManifest($manifestPath);
    }

    /**
     * @param string[] $uploadedSourcePaths
     *
     * @return UploadedFile[]
     */
    private function createUploadedFileCollection(array $uploadedSourcePaths): array
    {
        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        \assert($basilFixtureHandler instanceof BasilFixtureHandler);

        return $basilFixtureHandler->createUploadFileCollection($uploadedSourcePaths);
    }
}
