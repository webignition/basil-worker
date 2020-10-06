<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Test;

class TestTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $source = '/app/basil/Test/test.yml';
        $position = 1;
        $manifestPath = 'manifests/manifest-test.yml';

        $test = Test::create($source, $manifestPath, $position);
        self::assertNull($test->getId());
        self::assertSame($source, $test->getSource());
        self::assertSame($manifestPath, $test->getManifestPath());
        self:self::assertSame($position, $test->getPosition());

        $this->entityManager->persist($test);
        $this->entityManager->flush();
        self::assertIsInt($test->getId());
    }
}
