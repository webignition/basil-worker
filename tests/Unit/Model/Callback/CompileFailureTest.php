<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Callback;

use App\Model\Callback\CompileFailure;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use webignition\BasilCompilerModels\ErrorOutput;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompileFailureTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetType()
    {
        $callback = new CompileFailure(\Mockery::mock(ErrorOutputInterface::class));

        self::assertSame(CompileFailure::TYPE, $callback->getType());
    }

    public function testGetData()
    {
        $sourceData = [
            'config' => [
                'source' => '{{ COMPILER_SOURCE_DIRECTORY }}/InvalidTest/invalid-unparseable-assertion.yml',
                'target' => '{{ COMPILER_TARGET_DIRECTORY }}',
                'base-class' => 'webignition\BaseBasilTestCase\AbstractBaseTest',
            ],
            'error' => [
                'message' => 'Unparseable test',
                'code' => 206,
                'context' => [
                    'type' => 'test',
                    'test_path' =>
                        '{{ COMPILER_SOURCE_DIRECTORY }}/InvalidTest/invalid-unparseable-assertion.yml',
                    'step_name' => 'verify page is open',
                    'reason' => 'empty-value',
                    'statement_type' => 'assertion',
                    'statement' => '$page.url is',
                ],
            ],
        ];

        $callback = new CompileFailure(ErrorOutput::fromArray($sourceData));

        self::assertSame($sourceData, $callback->getData());
    }

    public function testSendAttemptCount()
    {
        $callback = new CompileFailure(\Mockery::mock(ErrorOutputInterface::class));
        self::assertSame(0, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(1, $callback->getRetryCount());

        $callback->incrementRetryCount();
        self::assertSame(2, $callback->getRetryCount());
    }
}
