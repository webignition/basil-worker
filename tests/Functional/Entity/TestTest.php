<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Test;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\Configuration;

class TestTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $source = '/app/basil/Test/test.yml';
        $position = 1;
        $manifestPath = 'manifests/manifest-test.yml';

        $manifest = new TestManifest(
            new Configuration('chrome', 'http://example.com'),
            'Test/test1.yml',
            'generated/GeneratedTest1.php',
            3
        );

        $test = Test::create($source, $manifest, $manifestPath, $position);
        self::assertNull($test->getId());
        self::assertSame($source, $test->getSource());
        self::assertSame($manifestPath, $test->getManifestPath());
        self:self::assertSame($position, $test->getPosition());

        $this->entityManager->persist($test);
        $this->entityManager->flush();
        self::assertIsInt($test->getId());
    }
}
