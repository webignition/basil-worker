<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Callback\AbstractCallbackEntityWrapper;
use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;

class FooTestCallback extends AbstractCallbackEntityWrapper
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

    public function getPayload(): array
    {
        return $this->payload;
    }
}
