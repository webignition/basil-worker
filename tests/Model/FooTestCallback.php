<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Callback\AbstractCallbackEntityWrapper;
use App\Entity\Callback\CallbackEntity;

class FooTestCallback extends AbstractCallbackEntityWrapper
{
    private const ID = 'id';
    private const TYPE = 'test';

    /**
     * @var array<mixed>
     */
    private array $payload;

    public function __construct()
    {
        $this->payload = [
            self::ID => random_bytes(16),
        ];

        parent::__construct(CallbackEntity::create(
            self::TYPE,
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

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
