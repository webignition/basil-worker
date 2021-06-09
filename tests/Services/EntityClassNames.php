<?php

declare(strict_types=1);

namespace App\Tests\Services;

class EntityClassNames
{
    /**
     * @param array<class-string> $entityClassNames
     */
    public function __construct(
        private array $entityClassNames,
    ) {
    }

    /**
     * @return array<class-string>
     */
    public function get(): array
    {
        return $this->entityClassNames;
    }
}
