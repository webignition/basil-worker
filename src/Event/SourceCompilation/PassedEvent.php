<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use webignition\BasilCompilerModels\SuiteManifest;

class PassedEvent extends AbstractEvent implements OutcomeEventInterface
{
    public function __construct(string $source, private SuiteManifest $suiteManifest)
    {
        parent::__construct($source);
    }

    public function getOutput(): SuiteManifest
    {
        return $this->suiteManifest;
    }
}
