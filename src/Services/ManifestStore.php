<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Yaml\Dumper;
use webignition\BasilCompilerModels\TestManifest;

class ManifestStore
{
    private const YAML_DUMP_INLINE_DEPTH = 4;

    private string $manifestDirectory;
    private ManifestPathGenerator $pathGenerator;
    private Dumper $yamlDumper;

    public function __construct(
        string $manifestDirectory,
        ManifestPathGenerator $pathGenerator,
        Dumper $yamlDumper
    ) {
        $this->manifestDirectory = $manifestDirectory;
        $this->pathGenerator = $pathGenerator;
        $this->yamlDumper = $yamlDumper;
    }

    public function store(TestManifest $testManifest): string
    {
        $path = $this->manifestDirectory . '/' . $this->pathGenerator->generate($testManifest);
        $yaml = $this->yamlDumper->dump($testManifest->getData(), self::YAML_DUMP_INLINE_DEPTH);

        file_put_contents($path, $yaml);

        return $path;
    }
}
