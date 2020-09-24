<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Job
{
    public const ID = 1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private int $id = self::ID;

    /**
     * @ORM\ManyToOne(targetEntity=JobState::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private ?JobState $state = null;

    /**
     * @ORM\Column(type="string", length=32, nullable=false, unique=true)
     */
    private ?string $label = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $callbackUrl;

    /**
     * @ORM\Column(type="simple_array")
     *
     * @var string[]
     */
    private array $sources = [];

    /**
     * @param JobState $state
     * @param string $label
     * @param string $callbackUrl
     * @param string[] $sources
     *
     * @return self
     */
    public static function create(JobState $state, string $label, string $callbackUrl, array $sources): self
    {
        $job = new Job();
        $job->state = $state;
        $job->label = $label;
        $job->callbackUrl = $callbackUrl;
        $job->sources = $sources;

        return $job;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?JobState
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
}
