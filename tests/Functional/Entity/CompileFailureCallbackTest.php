<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\CallbackEntityInterface;
use App\Entity\CompileFailureCallback;
use webignition\BasilCompilerModels\ErrorOutputInterface;

class CompileFailureCallbackTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $payload = [
            'key1' => 'value1',
            'key2' => [
                'key2key1' => 'key2 value1',
                'key2key2' => 'key2 value2',
            ],
        ];

        $errorOutput = \Mockery::mock(ErrorOutputInterface::class);
        $errorOutput
            ->shouldReceive('getData')
            ->andReturn($payload);

        $callback = CompileFailureCallback::create($errorOutput);

        self::assertNull($callback->getId());
        self::assertSame(CallbackEntityInterface::STATE_AWAITING, $callback->getState());
        self::assertSame(0, $callback->getRetryCount());
        self::assertSame(CallbackEntityInterface::TYPE_COMPILE_FAILURE, $callback->getType());
        self::assertSame($payload, $callback->getPayload());

        $this->entityManager->persist($callback);
        $this->entityManager->flush();
        self::assertIsInt($callback->getId());
    }
}
