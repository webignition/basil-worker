<?php

declare(strict_types=1);

namespace App\Entity\Callback;

abstract class AbstractCallbackEntityWrapper implements CallbackInterface
{
    private CallbackInterface $callback;

    public function __construct(CallbackInterface $callback)
    {
        $this->callback = $callback;
    }

    public function getEntity(): CallbackEntity
    {
        return $this->callback->getEntity();
    }

    public function getId(): ?int
    {
        return $this->callback->getId();
    }

    public function getState(): string
    {
        return $this->callback->getState();
    }

    public function setState(string $state): void
    {
        $this->callback->setState($state);
    }

    public function getRetryCount(): int
    {
        return $this->callback->getRetryCount();
    }

    public function getType(): string
    {
        return $this->callback->getType();
    }

    public function getPayload(): array
    {
        return $this->callback->getPayload();
    }

    public function incrementRetryCount(): void
    {
        $this->callback->incrementRetryCount();
    }

    public function hasReachedRetryLimit(int $limit): bool
    {
        return $this->callback->hasReachedRetryLimit($limit);
    }
}
