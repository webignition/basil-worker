<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity\Callback;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Services\TestConfigurationStore;
use App\Tests\Functional\Entity\AbstractEntityTest;

class CallbackEntityTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);

        $type = CallbackInterface::TYPE_COMPILE_FAILURE;
        $payload = [
            'key1' => 'value1',
            'key2' => [
                'key2key1' => 'key2 value1',
                'key2key2' => 'key2 value2',
            ],
        ];

        $callback = CallbackEntity::create($type, $payload);
        self::assertNull($callback->getId());
        self::assertSame(CallbackInterface::STATE_AWAITING, $callback->getState());
        self::assertSame(0, $callback->getRetryCount());
        self::assertSame($type, $callback->getType());
        self::assertSame($payload, $callback->getPayload());

        $this->entityManager->persist($callback);
        $this->entityManager->flush();
        self::assertIsInt($callback->getId());
    }
}
