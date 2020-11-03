<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Callback\ExecuteDocumentReceived;
use App\Model\Document\Test;
use Symfony\Component\Yaml\Dumper;
use webignition\YamlDocument\Document;

class ExecuteDocumentReceivedCallbackFactory
{
    private SourcePathTranslator $sourcePathTranslator;
    private Dumper $yamlDumper;

    public function __construct(SourcePathTranslator $sourcePathTranslator, Dumper $yamlDumper)
    {
        $this->sourcePathTranslator = $sourcePathTranslator;
        $this->yamlDumper = $yamlDumper;
    }

    public function create(Document $document): ExecuteDocumentReceived
    {
        $test = new Test($document);
        if ($test->isTest()) {
            $path = $test->getPath();

            if ($this->sourcePathTranslator->isPrefixedWithCompilerSourceDirectory($path)) {
                $mutatedPath = $this->sourcePathTranslator->stripCompilerSourceDirectoryFromPath($path);
                $mutatedTestSource = $this->yamlDumper->dump($test->getMutatedData([
                    Test::KEY_PATH => $mutatedPath,
                ]));

                $document = new Document($mutatedTestSource);
            }
        }

        return new ExecuteDocumentReceived($document);
    }
}
