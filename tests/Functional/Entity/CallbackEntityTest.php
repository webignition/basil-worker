<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Callback\CallbackEntity;

class CallbackEntityTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(CallbackEntity::class);
        self::assertCount(0, $repository->findAll());

        $callback = CallbackEntity::create(CallbackEntity::TYPE_COMPILATION_FAILED, []);

        $this->entityManager->persist($callback);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}
