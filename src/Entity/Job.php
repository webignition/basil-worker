<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Job implements \JsonSerializable
{
    public const STATE_COMPILATION_AWAITING = 'compilation-awaiting';
    public const STATE_COMPILATION_RUNNING = 'compilation-running';
    public const STATE_COMPILATION_FAILED = 'compilation-failed';
    public const STATE_EXECUTION_AWAITING = 'execution-awaiting';
    public const STATE_EXECUTION_RUNNING = 'execution-running';
    public const STATE_EXECUTION_FAILED = 'execution-failed';
    public const STATE_EXECUTION_COMPLETE = 'execution-complete';
    public const STATE_EXECUTION_CANCELLED = 'execution-cancelled';

    private const RUNNING_STATES = [
        self::STATE_COMPILATION_RUNNING,
        self::STATE_EXECUTION_RUNNING,
    ];

    private const FINISHED_STATES = [
        self::STATE_COMPILATION_FAILED,
        self::STATE_EXECUTION_FAILED,
        self::STATE_EXECUTION_COMPLETE,
        self::STATE_EXECUTION_CANCELLED,
    ];

    public const ID = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $id = self::ID;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     * @var Job::STATE_*
     */
    private string $state = self::STATE_COMPILATION_AWAITING;

    /**
     * @ORM\Column(type="string", length=32, nullable=false, unique=true)
     */
    private ?string $label = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $callbackUrl;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var string[]
     */
    private array $sources = [];

    public static function create(string $label, string $callbackUrl): self
    {
        $job = new Job();

        $job->label = $label;
        $job->callbackUrl = $callbackUrl;

        return $job;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Job::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @param string[] $sources
     */
    public function setSources(array $sources): void
    {
        $this->sources = $sources;
    }

    /**
     * @param Job::STATE_* $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function isRunning(): bool
    {
        return in_array($this->state, self::RUNNING_STATES);
    }

    public function isFinished(): bool
    {
        return in_array($this->state, self::FINISHED_STATES);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'state' => $this->state,
            'label' => $this->label,
            'callback_url' => $this->callbackUrl,
            'sources' => $this->sources,
        ];
    }
}
