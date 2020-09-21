<?php

namespace App\Tests\Functional\Entity;

use App\Entity\TestConfiguration;
use Doctrine\ORM\EntityManagerInterface;

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

        if ($this->entityManager instanceof EntityManagerInterface) {
            $this->entityManager->persist($configuration);
            $this->entityManager->flush();
        }

        self::assertIsInt($configuration->getId());
    }
}
