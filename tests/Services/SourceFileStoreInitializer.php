<?php

declare(strict_types=1);

namespace App\Tests\Services;

class SourceFileStoreInitializer
{
    public function __construct(
        private string $path
    ) {
    }

    public function initialize(): void
    {
        $this->clear();
    }

    public function clear(): bool
    {
        return $this->clearDirectory($this->path);
    }

    private function clearDirectory(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_file($path)) {
            return unlink($path);
        }

        $items = scandir($path);
        if (false === $items) {
            return false;
        }

        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->clearDirectory($path . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        if ($path !== $this->path) {
            return rmdir($path);
        }

        return true;
    }
}
