<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Services\TestConfigurationStore;
use App\Tests\AbstractBaseFunctionalTest;

class TestConfigurationStoreTest extends AbstractBaseFunctionalTest
{
    private TestConfigurationStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $store);

        if ($store instanceof TestConfigurationStore) {
            $this->store = $store;
        }
    }

    public function testFind()
    {
        $browser = 'chrome';
        $url = 'http://example.com';

        $testConfiguration = $this->store->find($browser, $url);

        self:self::assertInstanceOf(TestConfiguration::class, $testConfiguration);
        self::assertIsInt($testConfiguration->getId());
        self::assertSame($browser, $testConfiguration->getBrowser());
        self::assertSame($url, $testConfiguration->getUrl());

        self::assertSame(
            $testConfiguration,
            $this->store->find($browser, $url)
        );
    }
}
