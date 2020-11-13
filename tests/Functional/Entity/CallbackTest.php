<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Callback;
use App\Services\TestConfigurationStore;

class CallbackTest extends AbstractEntityTest
{
    public function testCreate()
    {
        $testConfigurationStore = self::$container->get(TestConfigurationStore::class);
        self::assertInstanceOf(TestConfigurationStore::class, $testConfigurationStore);

        $type = 'type';
        $payload = [
            'key1' => 'value1',
            'key2' => [
                'key2key1' => 'key2 value1',
                'key2key2' => 'key2 value2',
            ],
        ];

        $callback = Callback::create($type, $payload);
        self::assertNull($callback->getId());
        self::assertSame(Callback::STATE_AWAITING, $callback->getState());
        self::assertSame(0, $callback->getRetryCount());
        self::assertSame($type, $callback->getType());
        self::assertSame($payload, $callback->getPayload());

        $this->entityManager->persist($callback);
        $this->entityManager->flush();
        self::assertIsInt($callback->getId());
    }
}
