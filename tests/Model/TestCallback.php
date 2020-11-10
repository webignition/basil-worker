<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Callback\AbstractCallback;

class TestCallback extends AbstractCallback
{
    private const ID = 'id';
    private const TYPE = 'test';

    /**
     * @var array<mixed>
     */
    private array $data = [];

    public function __construct()
    {
        $this->data = [
            self::ID => random_bytes(16),
        ];
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
     * @param array<mixed> $data
     *
     * @return $this
     */
    public function withData(array $data): self
    {
        $new = clone $this;
        $new->data = $data;

        return $new;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
