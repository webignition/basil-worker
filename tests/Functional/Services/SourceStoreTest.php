<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\SourceStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\ServiceReference;
use App\Tests\Services\BasilFixtureHandler;
use App\Tests\Services\InvokableHandler;
use App\Tests\Services\SourceStoreInitializer;
use Symfony\Component\HttpFoundation\File\File;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class SourceStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private SourceStore $store;
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
            function (BasilFixtureHandler $basilFixtureHandler, string $uploadedFileFixturePath) {
                return $basilFixtureHandler->createUploadedFile($uploadedFileFixturePath);
            },
            [
                new ServiceReference(BasilFixtureHandler::class),
                $uploadedFileFixturePath
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
