<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class CompileFailureCallback extends AbstractCallbackWrapper
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
