<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Parser as YamlParser;
use webignition\BasilCompilerModels\ErrorOutput;
use webignition\BasilCompilerModels\OutputInterface;
use webignition\BasilCompilerModels\SuiteManifest;
use webignition\TcpCliProxyClient\Client;

class Compiler
{
    private Client $client;
    private string $compilerSourceDirectory;
    private string $compilerTargetDirectory;
    private YamlParser $yamlParser;

    public function __construct(
        Client $client,
        string $compilerSourceDirectory,
        string $compilerTargetDirectory,
        YamlParser $yamlParser
    ) {
        $this->client = $client;
        $this->compilerTargetDirectory = $compilerTargetDirectory;
        $this->compilerSourceDirectory = $compilerSourceDirectory;
        $this->yamlParser = $yamlParser;
    }

    public function compile(string $source): OutputInterface
    {
        $output = new BufferedOutput();
        $client = $this->client->withOutput($output);

        $client->request(sprintf(
            './compiler --source=%s --target=%s',
            $this->compilerSourceDirectory . '/' . $source,
            $this->compilerTargetDirectory
        ));

        $rawOutputLines = explode("\n", $output->fetch());
        $exitCode = (int) array_pop($rawOutputLines);
        $outputContent = trim(implode("\n", $rawOutputLines));
        $outputData = $this->yamlParser->parse($outputContent);

        if (0 === $exitCode) {
            return SuiteManifest::fromArray($outputData);
        }

        return ErrorOutput::fromArray($outputData);
    }
}
