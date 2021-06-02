<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use webignition\BasilCompilerModels\OutputInterface;

interface OutcomeEventInterface extends EventInterface
{
    public function getOutput(): OutputInterface;
}
