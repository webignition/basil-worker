<?php

namespace App\Tests\Functional\Entity;

use App\Entity\TestConfiguration;

class TestConfigurationTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $browser = 'chrome';
        $url = 'http://example.com';

        $configuration = TestConfiguration::create($browser, $url);
        self::assertNull($configuration->getId());
        self::assertSame($browser, $configuration->getBrowser());
        self::assertSame($url, $configuration->getUrl());

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
        self::assertIsInt($configuration->getId());
    }
}
