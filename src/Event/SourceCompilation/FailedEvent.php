<?php

declare(strict_types=1);

namespace App\Event\SourceCompilation;

use webignition\BasilCompilerModels\ErrorOutputInterface;

class FailedEvent extends AbstractEvent implements OutcomeEventInterface
{
    public function __construct(string $source, private ErrorOutputInterface $errorOutput)
    {
        parent::__construct($source);
    }

    public function getOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }
}
