<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Document\Test;
use Symfony\Component\Yaml\Dumper;
use webignition\YamlDocument\Document;

class TestDocumentMutator
{
    private SourcePathTranslator $sourcePathTranslator;
    private Dumper $yamlDumper;

    public function __construct(SourcePathTranslator $sourcePathTranslator, Dumper $yamlDumper)
    {
        $this->sourcePathTranslator = $sourcePathTranslator;
        $this->yamlDumper = $yamlDumper;
    }

    public function removeCompilerSourceDirectoryFromSource(Document $document): Document
    {
        $test = new Test($document);
        if ($test->isTest()) {
            $path = $test->getPath();

            $mutatedPath = $this->sourcePathTranslator->stripCompilerSourceDirectory($path);
            $mutatedTestSource = $this->yamlDumper->dump($test->getMutatedData([
                Test::KEY_PATH => $mutatedPath,
            ]));

            $document = new Document($mutatedTestSource);
        }

        return $document;
    }
}
