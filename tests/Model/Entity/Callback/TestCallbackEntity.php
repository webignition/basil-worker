<?php

declare(strict_types=1);

namespace App\Tests\Model\Entity\Callback;

use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class TestCallbackEntity extends \App\Model\Callback\AbstractCallbackWrapper implements CallbackInterface
{
    private ?int $id = null;

    public static function createWithUniquePayload(): self
    {
        return new TestCallbackEntity(
            CallbackEntity::create(
                CallbackInterface::TYPE_COMPILE_FAILURE,
                [
                    'unique' => md5(random_bytes(16)),
                ]
            )
        );
    }

    public function getId(): ?int
    {
        return is_int($this->id) ? $this->id : parent::getId();
    }

    public function withId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
