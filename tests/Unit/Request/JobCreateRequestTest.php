<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\JobCreateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class JobCreateRequestTest extends TestCase
{
    public function testCreate()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';
        $manifest = \Mockery::mock(UploadedFile::class);
        $sources = [
            \Mockery::mock(UploadedFile::class),
            \Mockery::mock(UploadedFile::class),
        ];

        $jobCreateRequest = new JobCreateRequest($label, $callbackUrl, $manifest, $sources);

        self::assertSame($label, $jobCreateRequest->getLabel());
        self::assertSame($callbackUrl, $jobCreateRequest->getCallbackUrl());
        self::assertSame($manifest, $jobCreateRequest->getManifest());
        self::assertSame($sources, $jobCreateRequest->getSources());
    }
}
