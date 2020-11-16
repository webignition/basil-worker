<?php

declare(strict_types=1);

namespace App\Tests\Model\Entity\Callback;

use App\Entity\Callback\AbstractCallbackEntityWrapper;
use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;

class TestCallbackEntity extends AbstractCallbackEntityWrapper implements CallbackInterface
{
    private const TYPE = 'test-type';

    private ?int $id = null;

    public static function createWithUniquePayload(): self
    {
        return new TestCallbackEntity(
            CallbackEntity::create(
                self::TYPE,
                [
                    'unique' => random_bytes(16),
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
