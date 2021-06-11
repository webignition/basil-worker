<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Symfony\Component\Finder\Finder;

class SourceFileStoreHandler
{
    public function __construct(
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
}
