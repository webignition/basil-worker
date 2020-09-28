<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\TestConfiguration;
use PHPUnit\Framework\TestCase;

class TestConfigurationTest extends TestCase
{
    public function testJsonSerialize()
    {
        $browser = 'chrome';
        $url = 'http://example.com';

        $testConfiguration = TestConfiguration::create($browser, $url);

        self::assertSame(
            [
                'browser' => $browser,
                'url' => $url,
            ],
            $testConfiguration->jsonSerialize()
        );
    }
}
