<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\SourceStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\SourceStoreInitializer;
use Symfony\Component\HttpFoundation\File\File;

class SourceStoreTest extends AbstractBaseFunctionalTest
{
    private SourceStore $sourceStore;
    private BasilFixtureHandler $basilFixtureHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceStore = self::$container->get(SourceStore::class);
        self::assertInstanceOf(SourceStore::class, $sourceStore);
        if ($sourceStore instanceof SourceStore) {
            $this->sourceStore = $sourceStore;
        }

        $sourceStoreInitializer = self::$container->get(SourceStoreInitializer::class);
        self::assertInstanceOf(SourceStoreInitializer::class, $sourceStoreInitializer);
        if ($sourceStoreInitializer instanceof SourceStoreInitializer) {
            $sourceStoreInitializer->initialize();
        }

        $basilFixtureHandler = self::$container->get(BasilFixtureHandler::class);
        self::assertInstanceOf(BasilFixtureHandler::class, $basilFixtureHandler);
        if ($basilFixtureHandler instanceof BasilFixtureHandler) {
            $this->basilFixtureHandler = $basilFixtureHandler;
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
        $expectedFilePath = $expectedFile->getPathname();
        self::assertFileDoesNotExist($expectedFilePath);

        $uploadedFile = $this->basilFixtureHandler->createUploadedFile($uploadedFileFixturePath);

        $file = $this->sourceStore->store($uploadedFile, $relativePath);

        self::assertEquals($expectedFile->getPathname(), $file->getPathname());
        self::assertFileExists($expectedFilePath);
    }

    public function storeDataProvider(): array
    {
        return [
            'default' => [
                'uploadedFileFixturePath' => 'Test/test1.yml',
                'relativePath' => 'Test/test1.yml',
                'expectedFile' => new File(getcwd() . '/var/basil/Test/test1.yml', false),
            ],
        ];
    }
}
