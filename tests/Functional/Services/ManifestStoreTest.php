<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\ManifestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockTestManifest;
use App\Tests\Mock\Services\MockManifestPathGenerator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class ManifestStoreTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private ManifestStore $suiteManifestStore;

    protected function setUp(): void
    {
        parent::setUp();

        $suiteManifestStore = self::$container->get(ManifestStore::class);
        if ($suiteManifestStore instanceof ManifestStore) {
            $this->suiteManifestStore = $suiteManifestStore;
        }
    }

    public function testStore()
    {
        $testManifest = (new MockTestManifest())
            ->withGetDataCall([
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ])
            ->getMock();

        $generatedPath = 'generated-manifest.yml';
        $pathGenerator = (new MockManifestPathGenerator())
            ->withGenerateCall($testManifest, $generatedPath)
            ->getMock();

        ObjectReflector::setProperty(
            $this->suiteManifestStore,
            ManifestStore::class,
            'pathGenerator',
            $pathGenerator
        );

        $path = $this->suiteManifestStore->store($testManifest);

        self::assertFileExists($path);
        self::assertSame(
            'key1: value1' . "\n" . 'key2: value2' . "\n" . 'key3: value3' . "\n",
            file_get_contents($path)
        );

        unlink($path);
    }
}
