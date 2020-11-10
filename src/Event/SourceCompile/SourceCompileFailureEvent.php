<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use App\Event\CallbackEventInterface;
use App\Model\Callback\CallbackInterface;
use App\Model\Callback\CompileFailure;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class SourceCompileFailureEvent extends AbstractSourceCompileEvent implements CallbackEventInterface
{
    private ErrorOutputInterface $errorOutput;

    public function __construct(string $source, ErrorOutputInterface $errorOutput)
    {
        parent::__construct($source);
        $this->errorOutput = $errorOutput;
    }

    public function getOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }

    public function getCallback(): CallbackInterface
    {
        return new CompileFailure($this->errorOutput);
    }
}
