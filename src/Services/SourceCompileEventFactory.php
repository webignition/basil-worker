<?php

declare(strict_types=1);

namespace App\Services;

use App\Event\SourceCompileEventInterface;
use App\Event\SourceCompileFailureEvent;
use App\Event\SourceCompileSuccessEvent;
use webignition\BasilCompilerModels\ErrorOutputInterface;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;

class SourceCompileEventFactory
{
    public function create(string $source, OutputInterface $output): ?SourceCompileEventInterface
    {
        if ($output instanceof ErrorOutputInterface) {
            return new SourceCompileFailureEvent($source, $output);
        }

        if ($output instanceof SuiteManifest) {
            return new SourceCompileSuccessEvent($source, $output);
        }

        return null;
    }
}
