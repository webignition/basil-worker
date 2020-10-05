<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Yaml\Dumper;
use webignition\BasilCompilerModels\SuiteManifest;

class SuiteManifestStore
{
    private const YAML_DUMP_INLINE_DEPTH = 4;

    private string $manifestDirectory;
    private SuiteManifestPathGenerator $pathGenerator;
    private Dumper $yamlDumper;

    public function __construct(
        string $manifestDirectory,
        SuiteManifestPathGenerator $pathGenerator,
        Dumper $yamlDumper
    ) {
        $this->manifestDirectory = $manifestDirectory;
        $this->pathGenerator = $pathGenerator;
        $this->yamlDumper = $yamlDumper;
    }

    public function store(SuiteManifest $suiteManifest): string
    {
        $path = $this->manifestDirectory . '/' . $this->pathGenerator->generate($suiteManifest);
        $yaml = $this->yamlDumper->dump($suiteManifest->getData(), self::YAML_DUMP_INLINE_DEPTH);

        file_put_contents($path, $yaml);

        return $path;
    }
}
