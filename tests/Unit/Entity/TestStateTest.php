<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\TestState;
use PHPUnit\Framework\TestCase;

class TestStateTest extends TestCase
{
    public function testJsonSerialize()
    {
        $name = 'test state name';
        $testState = TestState::create($name);

        self::assertSame($name, $testState->jsonSerialize());
    }
}
