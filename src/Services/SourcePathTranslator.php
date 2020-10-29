<?php

declare(strict_types=1);

namespace App\Services;

class SourcePathTranslator
{
    private string $compilerSourceDirectory;

    public function __construct(string $compilerSourceDirectory)
    {
        $this->compilerSourceDirectory = $compilerSourceDirectory;
    }

    public function translateJobSourceToTestSource(string $jobSource): string
    {
        return $this->compilerSourceDirectory . '/' . $jobSource;
    }
}
