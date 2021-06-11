<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Model\UploadedFileKey;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BasilFixtureHandler
{
    private string $fixturesPath;
    private string $uploadedPath;

    public function __construct(string $fixturesPath, string $uploadedPath)
    {
        $this->fixturesPath = $fixturesPath;
        $this->uploadedPath = $uploadedPath;
    }

    public function storeUploadedFile(string $relativePath): string
    {
        $fixturePath = $this->fixturesPath . '/' . $relativePath;
        $uploadedFilePath = $this->uploadedPath . '/' . $relativePath;

        if (!file_exists($uploadedFilePath)) {
            $directory = dirname($uploadedFilePath);
            if (!file_exists($directory)) {
                var_dump($directory);

                mkdir($directory, 0777, true);
            }

            copy($fixturePath, $uploadedFilePath);
        }

        return $uploadedFilePath;
    }

    public function emptyUploadedPath(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->uploadedPath);

        foreach ($finder as $file) {
            $path = $file->getPathname();

            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function createUploadedFile(string $relativePath): UploadedFile
    {
        $uploadedFilePath = $this->storeUploadedFile($relativePath);

        return new UploadedFile($uploadedFilePath, '', 'text/yaml', null, true);
    }

    /**
     * @param string[] $relativePaths
     *
     * @return UploadedFile[]
     */
    public function createUploadFileCollection(array $relativePaths): array
    {
        $uploadedFiles = [];

        foreach ($relativePaths as $relativePath) {
            $key = new UploadedFileKey($relativePath);

            $uploadedFiles[$key->encode()] = $this->createUploadedFile($relativePath);
        }

        return $uploadedFiles;
    }
}
