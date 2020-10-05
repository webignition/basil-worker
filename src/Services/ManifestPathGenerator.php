<?php

declare(strict_types=1);

namespace App\Services;

use webignition\BasilCompilerModels\TestManifest;

class ManifestPathGenerator
{
    public function generate(TestManifest $manifest): string
    {
        return 'manifest' . md5((string) json_encode($manifest->getData())) . '.yml';
    }
}
