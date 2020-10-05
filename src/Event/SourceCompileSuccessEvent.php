<?php

declare(strict_types=1);

namespace App\Event;

use webignition\BasilCompilerModels\SuiteManifest;

class SourceCompileSuccessEvent extends AbstractSourceCompileEvent
{
    public const NAME = 'worker.source.compile.success';

    private SuiteManifest $suiteManifest;

    public function __construct(string $source, SuiteManifest $suiteManifest)
    {
        parent::__construct($source);
        $this->suiteManifest = $suiteManifest;
    }

    public function getSuiteManifest(): SuiteManifest
    {
        return $this->suiteManifest;
    }
}
