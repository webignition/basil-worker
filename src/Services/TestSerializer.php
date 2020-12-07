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

    /**
     * @param Test[] $tests
     *
     * @return array<mixed>
     */
    public function serializeCollection(array $tests): array
    {
        $serializedTests = [];

        foreach ($tests as $test) {
            if ($test instanceof Test) {
                $serializedTests[] = $this->serialize($test);
            }
        }

        return $serializedTests;
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
