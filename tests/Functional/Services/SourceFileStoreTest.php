<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\UploadedSource;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\FileStoreHandler;
use Symfony\Component\HttpFoundation\File\File;

class SourceFileStoreTest extends AbstractBaseFunctionalTest
{
    private SourceFileStore $store;
    private FileStoreHandler $fileStoreHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(SourceFileStore::class);
        \assert($store instanceof SourceFileStore);
        $this->store = $store;

        $fileStoreHandler = self::$container->get('app.tests.services.file_store_handler.source');
        \assert($fileStoreHandler instanceof FileStoreHandler);
        $this->fileStoreHandler = $fileStoreHandler;
        $this->fileStoreHandler->clear();
    }

    protected function tearDown(): void
    {
        $this->fileStoreHandler->clear();

        parent::tearDown();
    }

    /**
     * @dataProvider storeDataProvider
     */
    public function testStore(
        string $uploadedFileFixturePath,
        string $relativePath,
        File $expectedFile
    ): void {
        self::assertFalse($this->store->has($relativePath));

        $expectedFilePath = $expectedFile->getPathname();
        self::assertFileDoesNotExist($expectedFilePath);

        $uploadedSource = $this->createUploadedSource($uploadedFileFixturePath, $relativePath);
        $file = $this->store->store($uploadedSource, $relativePath);

        self::assertEquals($expectedFile->getPathname(), $file->getPathname());
        self::assertFileExists($expectedFilePath);
        self::assertTrue($this->store->has($relativePath));
    }

    /**
     * @return array[]
     */
    public function storeDataProvider(): array
    {
        return [
            'default' => [
                'uploadedFileFixturePath' => 'Test/chrome-open-index.yml',
                'relativePath' => 'Test/chrome-open-index.yml',
                'expectedFile' => new File(
                    getcwd() . '/tests/Fixtures/CompilerSource/Test/chrome-open-index.yml',
                    false
                ),
            ],
        ];
    }

    private function createUploadedSource(string $uploadedFileFixturePath, string $relativePath): UploadedSource
    {
        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        \assert($basilFixtureHandler instanceof BasilFixtureHandler);

        $uploadedFile = $basilFixtureHandler->createUploadedFile($uploadedFileFixturePath);

        return new UploadedSource($relativePath, $uploadedFile);
    }
}
