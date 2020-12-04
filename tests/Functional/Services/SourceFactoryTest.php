<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\Manifest;
use App\Model\UploadedSource;
use App\Model\UploadedSourceCollection;
use App\Services\SourceFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceStoreInitializer;
use App\Tests\Services\UploadedFileFactory;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceFactoryTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private SourceFactory $factory;
    private InvokableHandler $invokableHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();

        $sourceStoreInitializer = self::$container->get(SourceStoreInitializer::class);
        self::assertInstanceOf(SourceStoreInitializer::class, $sourceStoreInitializer);
        if ($sourceStoreInitializer instanceof SourceStoreInitializer) {
            $sourceStoreInitializer->initialize();
        }
    }

    /**
     * @dataProvider createCollectionFromManifestDataProvider
     *
     * @param string[] $uploadedSourcePaths
     * @param string[] $expectedStoredTestPaths
     */
    public function testCreateCollectionFromManifest(
        string $manifestPath,
        array $uploadedSourcePaths,
        array $expectedStoredTestPaths
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

        $storedTestPaths = $this->factory->createCollectionFromManifest($manifest, $uploadedSources);

        self::assertSame($expectedStoredTestPaths, $storedTestPaths);
    }

    public function createCollectionFromManifestDataProvider(): array
    {
        return [
            'empty manifest' => [
                'manifestPath' => getcwd() . '/tests/Fixtures/Manifest/empty.txt',
                'uploadedSourcePaths' => [],
                'expectedStoredTestPaths' => [],
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
            ],
        ];
    }
}
