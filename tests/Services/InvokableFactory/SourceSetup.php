<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Source;

class SourceSetup
{
    /**
     * @var Source::TYPE_*
     */
    private string $type;

    private string $path;

    public function __construct()
    {
        $this->type = Source::TYPE_TEST;
        $this->path = '';
    }

    /**
     * @return Source::TYPE_*
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param Source::TYPE_* $type
     */
    public function withType(string $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    public function withPath(string $path): self
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }
}
