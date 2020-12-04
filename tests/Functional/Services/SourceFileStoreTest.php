<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\UploadedSource;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use Symfony\Component\HttpFoundation\File\File;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceFileStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private SourceFileStore $store;
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
     * @dataProvider storeDataProvider
     */
    public function testStore(
        string $uploadedFileFixturePath,
        string $relativePath,
        File $expectedFile
    ) {
        self::assertFalse($this->store->has($relativePath));

        $expectedFilePath = $expectedFile->getPathname();
        self::assertFileDoesNotExist($expectedFilePath);

        $uploadedFile = $this->invokableHandler->invoke(new Invokable(
            function (BasilFixtureHandler $basilFixtureHandler, string $uploadedFileFixturePath, string $relativePath) {
                $uploadedFile = $basilFixtureHandler->createUploadedFile($uploadedFileFixturePath);

                return new UploadedSource($relativePath, $uploadedFile);
            },
            [
                new ServiceReference(BasilFixtureHandler::class),
                $uploadedFileFixturePath,
                $relativePath
            ]
        ));

        $file = $this->store->store($uploadedFile, $relativePath);

        self::assertEquals($expectedFile->getPathname(), $file->getPathname());
        self::assertFileExists($expectedFilePath);
        self::assertTrue($this->store->has($relativePath));
    }

    public function storeDataProvider(): array
    {
        return [
            'default' => [
                'uploadedFileFixturePath' => 'Test/chrome-open-index.yml',
                'relativePath' => 'Test/chrome-open-index.yml',
                'expectedFile' => new File(getcwd() . '/var/basil/Test/chrome-open-index.yml', false),
            ],
        ];
    }
}
