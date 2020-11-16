<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompile\SourceCompileEventInterface;
use App\Event\SourceCompile\SourceCompileSuccessEvent;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;

class SourceCompileEventFactory
{
    private CallbackEventFactory $callbackEventFactory;

    public function __construct(CallbackEventFactory $callbackEventFactory)
    {
        $this->callbackEventFactory = $callbackEventFactory;
    }

    public function create(string $source, OutputInterface $output): ?SourceCompileEventInterface
    {
        if ($output instanceof ErrorOutputInterface) {
            return $this->callbackEventFactory->createSourceCompileFailureEvent($source, $output);
        }

        if ($output instanceof SuiteManifest) {
            return new SourceCompileSuccessEvent($source, $output);
        }

        return null;
    }
}
