<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CallbackEntity
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_QUEUED = 'queued';
    public const STATE_SENDING = 'sending';
    public const STATE_FAILED = 'failed';
    public const STATE_COMPLETE = 'complete';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var CallbackEntity::STATE_*
     */
    private string $state;

    /**
     * @ORM\Column(type="smallint")
     */
    private int $retryCount;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $type;

    /**
     * @ORM\Column(type="json")
     *
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @param string $type
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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return CallbackEntity::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param CallbackEntity::STATE_* $state
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
}
