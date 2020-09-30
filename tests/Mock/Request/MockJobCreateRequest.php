<?php

declare(strict_types=1);

namespace App\Tests\Mock\Request;

use App\Request\JobCreateRequest;
use Mockery\MockInterface;

class MockJobCreateRequest
{
    /**
     * @var JobCreateRequest|MockInterface
     */
    private JobCreateRequest $jobCreateRequest;

    public function __construct()
    {
        $this->jobCreateRequest = \Mockery::mock(JobCreateRequest::class);
    }

    public function getMock(): JobCreateRequest
    {
        return $this->jobCreateRequest;
    }

    public function withGetLabelCall(string $label): self
    {
        $this->jobCreateRequest
            ->shouldReceive('getLabel')
            ->andReturn($label);

        return $this;
    }

    public function withGetCallbackUrlCall(string $callbackUrl): self
    {
        $this->jobCreateRequest
            ->shouldReceive('getCallbackUrl')
            ->andReturn($callbackUrl);

        return $this;
    }
}
