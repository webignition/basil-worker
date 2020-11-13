<?php

declare(strict_types=1);

namespace App\Entity;

use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompileFailureCallback extends CallbackEntity
{
    public static function create(ErrorOutputInterface $errorOutput): CallbackEntityInterface
    {
        return parent::createForTypeAndPayload(self::TYPE_COMPILE_FAILURE, $errorOutput->getData());
    }
}
