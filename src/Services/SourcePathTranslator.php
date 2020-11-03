<?php

declare(strict_types=1);

namespace App\Services;

class SourcePathTranslator
{
    private string $compilerSourceDirectory;
    private int $compilerSourceDirectoryLength;

    public function __construct(string $compilerSourceDirectory)
    {
        $this->compilerSourceDirectory = $compilerSourceDirectory;
        $this->compilerSourceDirectoryLength = strlen($compilerSourceDirectory);
    }

    public function translateJobSourceToTestSource(string $jobSource): string
    {
        return $this->compilerSourceDirectory . '/' . $jobSource;
    }

    public function isPrefixedWithCompilerSourceDirectory(string $path): bool
    {
        if (strlen($path) < $this->compilerSourceDirectoryLength) {
            return false;
        }

        $prefix = substr($path, 0, $this->compilerSourceDirectoryLength);
        if ($prefix !== $this->compilerSourceDirectory) {
            return false;
        }

        return true;
    }

    public function stripCompilerSourceDirectoryFromPath(string $path): string
    {
        if (false === $this->isPrefixedWithCompilerSourceDirectory($path)) {
            return $path;
        }

        $path = substr($path, $this->compilerSourceDirectoryLength);
        return ltrim($path, '/');
    }
}
