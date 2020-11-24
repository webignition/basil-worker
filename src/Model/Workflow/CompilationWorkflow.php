<?php

declare(strict_types=1);

namespace App\Model\Workflow;

class CompilationWorkflow
{
    private ?string $nextSource;

    /**
     * @param string|null $nextSource
     */
    public function __construct(?string $nextSource)
    {
        $this->nextSource = $nextSource;
    }

    public function getNextSource(): ?string
    {
        return $this->nextSource;
    }
}
