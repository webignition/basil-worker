<?php

declare(strict_types=1);

namespace App\Tests\Mock\Request;

use App\Model\Manifest;
use App\Request\AddSourcesRequest;
use Mockery\MockInterface;

class MockAddSourcesRequest
{
    /**
     * @var AddSourcesRequest|MockInterface
     */
    private AddSourcesRequest $addSourcesRequest;

    public function __construct()
    {
        $this->addSourcesRequest = \Mockery::mock(AddSourcesRequest::class);
    }

    public function getMock(): AddSourcesRequest
    {
        return $this->addSourcesRequest;
    }

    public function withGetManifestCall(?Manifest $manifest): self
    {
        $this->addSourcesRequest
            ->shouldReceive('getManifest')
            ->andReturn($manifest);

        return $this;
    }

    /**
     * @param string[] $sources
     *
     * @return $this
     */
    public function withGetSourcesCall(array $sources): self
    {
        $this->addSourcesRequest
            ->shouldReceive('getSources')
            ->andReturn($sources);

        return $this;
    }
}
