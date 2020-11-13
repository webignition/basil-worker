<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\TestConfiguration;
use App\Services\TestConfigurationStore;
use App\Tests\AbstractBaseFunctionalTest;
use webignition\SymfonyTestServiceInjectorTrait\TestClassServicePropertyInjectorTrait;

class TestConfigurationStoreTest extends AbstractBaseFunctionalTest
{
    use TestClassServicePropertyInjectorTrait;

    private TestConfigurationStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injectContainerServicesIntoClassProperties();
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
