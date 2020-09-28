<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Services\SourceStore;

class SourceStoreInitializer
{
    private SourceStore $sourceStore;

    public function __construct(SourceStore $sourceStore)
    {
        $this->sourceStore = $sourceStore;
    }

    public function initialize(): void
    {
        $this->delete();
        $this->create();
    }

    public function create(): void
    {
        $basilPath = $this->sourceStore->getPath();

        if (!file_exists($basilPath)) {
            mkdir($basilPath, 0777, true);
        }
    }

    public function delete(): bool
    {
        return $this->deleteDirectory($this->sourceStore->getPath());
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
