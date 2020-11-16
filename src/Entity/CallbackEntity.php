<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\Callback\CallbackModelInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CallbackEntity implements CallbackEntityInterface
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
     * @var CallbackModelInterface::STATE_*
     */
    private string $state;

    /**
     * @ORM\Column(type="smallint")
     */
    private int $retryCount;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var CallbackModelInterface::TYPE_*
     */
    private string $type;

    /**
     * @ORM\Column(type="json")
     *
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @param CallbackModelInterface::TYPE_* $type
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
     * @return CallbackModelInterface::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param CallbackModelInterface::STATE_* $state
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
