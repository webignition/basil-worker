<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\Callback\CallbackEntity;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompileFailureCallback extends AbstractCallbackEntityWrapper
{
    private ErrorOutputInterface $errorOutput;

    public function __construct(ErrorOutputInterface $errorOutput)
    {
        $this->errorOutput = $errorOutput;

        parent::__construct(CallbackEntity::create(
            CallbackModelInterface::TYPE_COMPILE_FAILURE,
            $errorOutput->getData()
        ));
    }

    public function getErrorOutput(): ErrorOutputInterface
    {
        return $this->errorOutput;
    }
}
