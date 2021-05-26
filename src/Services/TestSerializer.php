<?php

declare(strict_types=1);

namespace App\Services;

use webignition\BasilWorker\PersistenceBundle\Entity\Test;
use webignition\StringPrefixRemover\DefinedStringPrefixRemover;

class TestSerializer
{
    private DefinedStringPrefixRemover $compilerSourcePathPrefixRemover;
    private DefinedStringPrefixRemover $compilerTargetPathPrefixRemover;

    public function __construct(
        DefinedStringPrefixRemover $compilerSourcePathPrefixRemover,
        DefinedStringPrefixRemover $compilerTargetPathPrefixRemover
    ) {
        $this->compilerSourcePathPrefixRemover = $compilerSourcePathPrefixRemover;
        $this->compilerTargetPathPrefixRemover = $compilerTargetPathPrefixRemover;
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
                'source' => $this->compilerSourcePathPrefixRemover->remove((string) $test->getSource()),
                'target' => $this->compilerTargetPathPrefixRemover->remove((string) $test->getTarget()),
            ]
        );
    }
}
