<?php

declare(strict_types=1);

namespace App\Model\Callback;

use App\Entity\Callback\CallbackEntity;

abstract class AbstractCallbackEntityWrapper implements CallbackEntityWrapperInterface
{
    private CallbackEntity $entity;

    public function __construct(CallbackEntity $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): CallbackEntity
    {
        return $this->entity;
    }

    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function getState(): string
    {
        return $this->entity->getState();
    }

    public function setState(string $state): void
    {
        $this->entity->setState($state);
    }

    public function getRetryCount(): int
    {
        return $this->entity->getRetryCount();
    }

    public function getType(): string
    {
        return $this->entity->getType();
    }

    public function getPayload(): array
    {
        return $this->entity->getPayload();
    }

    public function incrementRetryCount(): void
    {
        $this->entity->incrementRetryCount();
    }
}
