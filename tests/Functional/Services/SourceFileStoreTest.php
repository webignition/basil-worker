<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\UploadedSource;
use App\Services\SourceFileStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\SourceFileStoreInitializer;
use Symfony\Component\HttpFoundation\File\File;

class SourceFileStoreTest extends AbstractBaseFunctionalTest
{
    private SourceFileStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(SourceFileStore::class);
        \assert($store instanceof SourceFileStore);
        $this->store = $store;

        $sourceFileStoreInitializer = self::$container->get(SourceFileStoreInitializer::class);
        \assert($sourceFileStoreInitializer instanceof SourceFileStoreInitializer);
        $sourceFileStoreInitializer->initialize();
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
