<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Test;

class TestSerializer
{
    private SourcePathTranslator $sourcePathTranslator;

    public function __construct(SourcePathTranslator $sourcePathTranslator)
    {
        $this->sourcePathTranslator = $sourcePathTranslator;
    }

    public function serialize(Test $test): array
    {
        return array_merge(
            $test->jsonSerialize(),
            [
                'source' => $this->sourcePathTranslator->stripCompilerSourceDirectoryFromPath($test->getSource()),
                'target' => $this->sourcePathTranslator->stripCompilerTargetDirectoryFromPath($test->getTarget()),
            ]
        );
    }
}
