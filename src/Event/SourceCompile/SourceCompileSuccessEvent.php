<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use webignition\BasilCompilerModels\SuiteManifest;

class SourceCompileSuccessEvent extends AbstractSourceCompileEvent
{
    private SuiteManifest $suiteManifest;

    public function __construct(string $source, SuiteManifest $suiteManifest)
    {
        parent::__construct($source);
        $this->suiteManifest = $suiteManifest;
    }

    public function getOutput(): SuiteManifest
    {
        return $this->suiteManifest;
    }
}
