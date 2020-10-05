<?php

declare(strict_types=1);

namespace App\Services;

use webignition\BasilCompilerModels\SuiteManifest;

class SuiteManifestPathGenerator
{
    public function generate(SuiteManifest $suiteManifest): string
    {
        return 'manifest' . md5((string) json_encode($suiteManifest->getData())) . '.yml';
    }
}
