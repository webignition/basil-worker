<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Manifest
{
    private UploadedFile $uploadedFile;

    public function __construct(UploadedFile $uploadedFile)
    {
        $this->uploadedFile = $uploadedFile;
    }

    /**
     * @return string[]
     */
    public function getTestPaths(): array
    {
        if (0 !== $this->uploadedFile->getError()) {
            return [];
        }

        $content = (string) file_get_contents($this->uploadedFile->getPathname());

        $rawLines = explode("\n", $content);
        $lines = [];

        foreach ($rawLines as $line) {
            $line = trim($line);
            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
