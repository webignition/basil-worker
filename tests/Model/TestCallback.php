<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Callback\AbstractCallbackWrapper;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackEntity;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

class TestCallback extends AbstractCallbackWrapper
{
    private const ID = 'id';

    /**
     * @var array<mixed>
     */
    private array $payload;

    public function __construct()
    {
        $this->payload = [
            self::ID => md5(random_bytes(16)),
        ];

        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_COMPILE_FAILURE,
            $this->payload
        ));
    }

    public function withRetryCount(int $retryCount): self
    {
        $new = clone $this;
        for ($i = 0; $i < $retryCount; $i++) {
            $new->incrementRetryCount();
        }

        return $new;
    }

    /**
     * @param CallbackInterface::STATE_* $state
     *
     * @return $this
     */
    public function withState(string $state): self
    {
        $new = clone $this;
        $new->setState($state);

        return $new;
    }

    /**
     * @param array<mixed> $payload
     *
     * @return $this
     */
    public function withPayload(array $payload): self
    {
        $new = clone $this;
        $new->payload = $payload;

        return $new;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
