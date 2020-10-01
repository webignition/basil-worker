<?php

declare(strict_types=1);

namespace App\Event;

use webignition\BasilCompilerModels\TestManifest;

class SourceCompileSuccessEvent extends AbstractSourceCompileEvent
{
    public const NAME = 'worker.source.compile.success';

    /**
     * @var TestManifest[]
     */
    private array $testManifests;

    /**
     * @param string $source
     * @param TestManifest[] $testManifests
     */
    public function __construct(string $source, array $testManifests)
    {
        parent::__construct($source);
        $this->testManifests = $testManifests;
    }

    /**
     * @return TestManifest[]
     */
    public function getTestManifests(): array
    {
        return $this->testManifests;
    }
}
