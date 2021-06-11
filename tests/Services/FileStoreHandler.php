<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\Finder\Finder;

class FileStoreHandler
{
    public function __construct(
        private BasilFixtureHandler $basilFixtureHandler,
        private string $path
    ) {
    }

    public function clear(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->path);

        foreach ($finder as $file) {
            $path = $file->getPathname();

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function copyFixture(string $relativePath): string
    {
        $origin = $this->basilFixtureHandler->getPath($relativePath);
        $destination = $this->path . '/' . $relativePath;

        if (!file_exists($destination)) {
            $directory = dirname($destination);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }

            copy($origin, $destination);
        }

        return $destination;
    }

    /**
     * @param string[] $relativePaths
     *
     * @return array<string, string>
     */
    public function copyFixtures(array $relativePaths): array
    {
        $storedPaths = [];

        foreach ($relativePaths as $relativePath) {
            $storedPath = $this->copyFixture($relativePath);
            $storedPaths[$relativePath] = $storedPath;
        }

        return $storedPaths;
    }
}
