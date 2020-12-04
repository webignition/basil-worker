<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\InvalidTestSourceException;
use App\Exception\MissingTestSourceException;
use App\Model\Manifest;
use App\Model\UploadedSourceCollection;
use App\Services\SourceFactory;
use App\Services\SourceStore;
use PHPUnit\Framework\TestCase;

class SourceFactoryTest extends TestCase
{
    public function testCreateCollectionFromManifestThrowsMissingTestSourceException()
    {
        $factory = new SourceFactory(\Mockery::mock(SourceStore::class));

        $path = 'Test/test.yml';

        $manifest = \Mockery::mock(Manifest::class);
        $manifest
            ->shouldReceive('getTestPaths')
            ->andReturn([
                $path,
            ]);

        $uploadedSources = \Mockery::mock(UploadedSourceCollection::class);
        $uploadedSources
            ->shouldReceive('contains')
            ->with($path)
            ->andReturn(false);

        self::expectExceptionObject(new MissingTestSourceException($path));

        $factory->createCollectionFromManifest($manifest, $uploadedSources);
    }

    public function testCreateCollectionFromManifestThrowsInvalidTestSourceException()
    {
        $factory = new SourceFactory(\Mockery::mock(SourceStore::class));

        $path = 'Test/test.yml';

        $manifest = \Mockery::mock(Manifest::class);
        $manifest
            ->shouldReceive('getTestPaths')
            ->andReturn([
                $path,
            ]);

        $uploadedSources = \Mockery::mock(UploadedSourceCollection::class);
        $uploadedSources
            ->shouldReceive('contains')
            ->with($path)
            ->andReturn(true);

        $uploadedSources
            ->shouldReceive('offsetGet')
            ->with($path)
            ->andReturn(null);

        self::expectExceptionObject(new InvalidTestSourceException($path));

        $factory->createCollectionFromManifest($manifest, $uploadedSources);
    }
}
