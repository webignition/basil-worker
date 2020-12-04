<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Source;
use App\Model\Manifest;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;
use App\Repository\SourceRepository;
use App\Services\SourceFactory;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private SourceFactory $factory;
    private SourceFileStore $sourceFileStore;
    private SourceRepository $sourceRepository;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreInitializer::class);
        self::assertInstanceOf(SourceFileStoreInitializer::class, $sourceFileStoreInitializer);
        if ($sourceFileStoreInitializer instanceof SourceFileStoreInitializer) {
            $sourceFileStoreInitializer->initialize();
        }
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
    ) {
        $manifestUploadedFile = $this->invokableHandler->invoke(new Invokable(
            function (UploadedFileFactory $uploadedFileFactory, string $manifestPath) {
                return $uploadedFileFactory->createForManifest($manifestPath);
            },
            [
                new ServiceReference(UploadedFileFactory::class),
                $manifestPath,
            ]
        ));

        $uploadedSourceFiles = $this->invokableHandler->invoke(new Invokable(
            function (BasilFixtureHandler $basilFixtureHandler, array $uploadedSourcePaths) {
                return $basilFixtureHandler->createUploadFileCollection($uploadedSourcePaths);
            },
            [
                new ServiceReference(BasilFixtureHandler::class),
                $uploadedSourcePaths,
            ]
        ));

        $uploadedSources = new UploadedSourceCollection();
        foreach ($uploadedSourceFiles as $path => $uploadedFile) {
            $uploadedSources[] = new UploadedSource($path, $uploadedFile);
        }

        $manifest = new Manifest($manifestUploadedFile);

        self::assertCount(0, $this->sourceRepository->findAll());

        $storedTestPaths = $this->factory->createCollectionFromManifest($manifest, $uploadedSources);

        self::assertSame($expectedStoredTestPaths, $storedTestPaths);

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
}
