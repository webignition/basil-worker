<?php

declare(strict_types=1);

namespace App\Event;

use webignition\BasilCompilerModels\ErrorOutputInterface;

class SourceCompileFailureEvent extends AbstractSourceCompileEvent
{
    public const NAME = 'worker.source.compile.failure';

    private ErrorOutputInterface $errorOutput;

    public function __construct(string $source, ErrorOutputInterface $errorOutput)
    {
        parent::__construct($source);
        $this->errorOutput = $errorOutput;
    }

    public function getErrorOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }
}
