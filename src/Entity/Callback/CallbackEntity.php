<?php

declare(strict_types=1);

namespace App\Entity\Callback;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CallbackEntity implements CallbackInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var CallbackInterface::STATE_*
     */
    private string $state;

    /**
     * @ORM\Column(type="smallint")
     */
    private int $retryCount;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var CallbackInterface::TYPE_*
     */
    private string $type;

    /**
     * @ORM\Column(type="json")
     *
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed> $payload
     *
     * @return self
     */
    public static function create(string $type, array $payload): self
    {
        $callback = new CallbackEntity();
        $callback->state = self::STATE_AWAITING;
        $callback->retryCount = 0;
        $callback->type = $type;
        $callback->payload = $payload;

        return $callback;
    }

    public function getEntity(): CallbackEntity
    {
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return CallbackInterface::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param CallbackInterface::STATE_* $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function incrementRetryCount(): void
    {
        $this->retryCount++;
    }

    public function hasReachedRetryLimit(int $limit): bool
    {
        return false === ($this->retryCount < $limit);
    }
}
