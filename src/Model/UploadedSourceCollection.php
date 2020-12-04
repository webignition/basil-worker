<?php

declare(strict_types=1);

namespace App\Model;

/**
 * @implements \ArrayAccess<string, UploadedSource>
 */
class UploadedSourceCollection implements \ArrayAccess
{
    /**
     * @var UploadedSource[]
     */
    private array $uploadedSources = [];

    /**
     * @param array<mixed> $uploadedSources
     */
    public function __construct(array $uploadedSources = [])
    {
        foreach ($uploadedSources as $uploadedSource) {
            if ($uploadedSource instanceof UploadedSource) {
                $this->uploadedSources[$uploadedSource->getPath()] = $uploadedSource;
            }
        }
    }

    public function contains(string $path): bool
    {
        return $this->offsetExists($path);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->uploadedSources);
    }

    public function offsetGet($offset): ?UploadedSource
    {
        return $this->uploadedSources[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        if ($value instanceof UploadedSource) {
            $this->uploadedSources[$value->getPath()] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->uploadedSources[$offset]);
    }
}
