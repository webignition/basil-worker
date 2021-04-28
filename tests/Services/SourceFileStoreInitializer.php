<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\SourceFileStore;

class SourceFileStoreInitializer
{
    private SourceFileStore $sourceFileStore;

    public function __construct(SourceFileStore $sourceFileStore)
    {
        $this->sourceFileStore = $sourceFileStore;
    }

    public function initialize(): void
    {
        $this->delete();
        $this->create();
    }

    public function create(): void
    {
        $basilPath = $this->sourceFileStore->getPath();

        if (!file_exists($basilPath)) {
            mkdir($basilPath, 0777, true);
        }
    }

    public function delete(): bool
    {
        return $this->deleteDirectory($this->sourceFileStore->getPath());
    }

    private function deleteDirectory(string $path): bool
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

            if (!$this->deleteDirectory($path . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($path);
    }
}
