<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\SuiteManifestStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\MockSuiteManifest;
use App\Tests\Mock\Services\MockSuiteManifestPathGenerator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class SuiteManifestStoreTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private SuiteManifestStore $suiteManifestStore;

    protected function setUp(): void
    {
        parent::setUp();

        $suiteManifestStore = self::$container->get(SuiteManifestStore::class);
        if ($suiteManifestStore instanceof SuiteManifestStore) {
            $this->suiteManifestStore = $suiteManifestStore;
        }
    }

    public function testStore()
    {
        $suiteManifest = (new MockSuiteManifest())
            ->withGetDataCall([
                'key1' => 'value1',
                'key2' => 'value2',
                'key3' => 'value3',
            ])
            ->getMock();

        $generatedPath = 'generated-manifest.yml';
        $pathGenerator = (new MockSuiteManifestPathGenerator())
            ->withGenerateCall($suiteManifest, $generatedPath)
            ->getMock();

        ObjectReflector::setProperty(
            $this->suiteManifestStore,
            SuiteManifestStore::class,
            'pathGenerator',
            $pathGenerator
        );

        $path = $this->suiteManifestStore->store($suiteManifest);

        self::assertFileExists($path);
        self::assertSame(
            'key1: value1' . "\n" . 'key2: value2' . "\n" . 'key3: value3' . "\n",
            file_get_contents($path)
        );

        unlink($path);
    }
}
