<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\JobCreateRequest;
use PHPUnit\Framework\TestCase;

class JobCreateRequestTest extends TestCase
{
    public function testCreate()
    {
        $label = md5('label source');
        $callbackUrl = 'http://example.com/callback';

        $jobCreateRequest = new JobCreateRequest($label, $callbackUrl);

        self::assertSame($label, $jobCreateRequest->getLabel());
        self::assertSame($callbackUrl, $jobCreateRequest->getCallbackUrl());
    }
}
