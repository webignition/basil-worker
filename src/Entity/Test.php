<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Test
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestConfiguration")
     * @ORM\JoinColumn(name="test_configuration_id", referencedColumnName="id", nullable=false)
     */
    private ?TestConfiguration $configuration = null;

    /**
     * @ORM\Column(type="text")
     */
    private string $source;

    /**
     * @ORM\Column(type="text")
     */
    private string $target;

    /**
     * @ORM\Column(type="integer")
     */
    private int $stepCount = 0;

    public static function create(
        TestConfiguration $configuration,
        string $source,
        string $target,
        int $stepCount
    ): self {
        $test = new Test();
        $test->configuration = $configuration;
        $test->source = $source;
        $test->target = $target;
        $test->stepCount = $stepCount;

        return $test;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfiguration(): ?TestConfiguration
    {
        return $this->configuration;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function getStepCount(): int
    {
        return $this->stepCount;
    }
}
