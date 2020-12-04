<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model;

use App\Model\UploadedSourceCollection;
use Mockery\MockInterface;

class MockUploadedSourceCollection
{
    /**
     * @var UploadedSourceCollection|MockInterface
     */
    private UploadedSourceCollection $sources;

    public function __construct()
    {
        $this->sources = \Mockery::mock(UploadedSourceCollection::class);
    }

    public function getMock(): UploadedSourceCollection
    {
        return $this->sources;
    }
}
