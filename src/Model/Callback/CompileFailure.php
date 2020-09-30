<?php

declare(strict_types=1);

namespace App\Model\Callback;

use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompileFailure implements CallbackInterface
{
    public const TYPE = 'compile-failure';

    private ErrorOutputInterface $errorOutput;

    public function __construct(ErrorOutputInterface $errorOutput)
    {
        $this->errorOutput = $errorOutput;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->errorOutput->getData();
    }
}
