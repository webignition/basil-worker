<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use App\Entity\Callback\CallbackInterface;
use App\Entity\Callback\CompileFailureCallback;
use App\Event\FooCallbackEventInterface;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class FooSourceCompileFailureEvent extends AbstractSourceCompileEvent implements FooCallbackEventInterface
{
    private ErrorOutputInterface $errorOutput;
    private CallbackInterface $callback;

    public function __construct(string $source, ErrorOutputInterface $errorOutput, CompileFailureCallback $callback)
    {
        parent::__construct($source);
        $this->errorOutput = $errorOutput;
        $this->callback = $callback;
    }

    public function getOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }

    public function getCallback(): CallbackInterface
    {
        return $this->callback;
    }
}