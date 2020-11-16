<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompileFailureCallback extends AbstractCallbackEntityWrapper
{
    private ErrorOutputInterface $errorOutput;

    public function __construct(ErrorOutputInterface $errorOutput)
    {
        $this->errorOutput = $errorOutput;

        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_COMPILE_FAILURE,
            $errorOutput->getData()
        ));
    }

    public function getErrorOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }
}
