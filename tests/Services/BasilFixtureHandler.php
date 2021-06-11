<?php

declare(strict_types=1);

namespace App\Tests\Services;

class BasilFixtureHandler
{
    private string $fixturesPath;

    public function __construct(string $fixturesPath)
    {
        $this->fixturesPath = $fixturesPath;
    }

    public function getPath(string $relativePath): string
    {
        return $this->fixturesPath . '/' . $relativePath;
    }
}
