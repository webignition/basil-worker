<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Model\Manifest;
use App\Model\UploadedSourceCollection;
use App\Services\SourceFactory;
use Mockery\MockInterface;

class MockSourceFactory
{
    /**
     * @var SourceFactory|MockInterface
     */
    private SourceFactory $sourceFactory;

    public function __construct()
    {
        $this->sourceFactory = \Mockery::mock(SourceFactory::class);
    }

    public function getMock(): SourceFactory
    {
        return $this->sourceFactory;
    }

    public function withCreateCollectionFromManifestCallThrowingException(
        Manifest $manifest,
        UploadedSourceCollection $sources,
        \Exception $exception
    ): self {
        $this->sourceFactory
            ->shouldReceive('createCollectionFromManifest')
            ->with($manifest, $sources)
            ->andThrow($exception);

        return $this;
    }
}
