<?php

declare(strict_types=1);

namespace App\Event\SourceCompile;

use App\Event\CallbackEventInterface;
use App\Model\Callback\CompileFailureCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class SourceCompileFailureEvent extends AbstractSourceCompileEvent implements CallbackEventInterface
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
