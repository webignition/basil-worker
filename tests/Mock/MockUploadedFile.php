<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MockUploadedFile
{
    /**
     * @var UploadedFile|MockInterface
     */
    private UploadedFile $uploadedFile;

    public function __construct()
    {
        $this->uploadedFile = \Mockery::mock(UploadedFile::class);
    }

    public function getMock(): UploadedFile
    {
        return $this->uploadedFile;
    }

    public function withGetErrorCall(int $error): self
    {
        $this->uploadedFile
            ->shouldReceive('getError')
            ->andReturn($error);

        return $this;
    }

    public function withGetPathnameCall(string $pathname): self
    {
        $this->uploadedFile
            ->shouldReceive('getPathname')
            ->andReturn($pathname);

        return $this;
    }
}
