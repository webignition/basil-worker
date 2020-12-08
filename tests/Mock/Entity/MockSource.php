<?php

declare(strict_types=1);

namespace App\Tests\Mock\Entity;

use Mockery\MockInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Source;

class MockSource
{
    /**
     * @var Source|MockInterface
     */
    private Source $source;

    public function __construct()
    {
        $this->source = \Mockery::mock(Source::class);
    }

    public function getMock(): Source
    {
        return $this->source;
    }
}
