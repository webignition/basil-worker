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
     * @return array<int, array<mixed>>
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

    /**
     * @param Test $test
     *
     * @return array<mixed>
     */
    public function serialize(Test $test): array
    {
        return array_merge(
            $test->jsonSerialize(),
            [
                'source' => $this->sourcePathTranslator->stripCompilerSourceDirectory((string) $test->getSource()),
                'target' => $this->sourcePathTranslator->stripCompilerTargetDirectory((string) $test->getTarget()),
            ]
        );
    }
}
