<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use webignition\BasilCompilerModels\TestManifest;
use webignition\BasilModels\Test\ConfigurationInterface as TestConfigurationInterface;

/**
 * @ORM\Entity
 */
class Test implements \JsonSerializable
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RUNNING = 'running';
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
     */
    private string $state;

    /**
     * @ORM\Column(type="text")
     */
    private string $source;

    /**
     * @ORM\Column(type="integer", nullable=false, unique=true)
     */
    private int $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private string $manifestPath;

    private ?TestManifest $manifest = null;

    public static function create(string $source, string $manifestPath, int $position): self
    {
        $test = new Test();
        $test->state = self::STATE_AWAITING;
        $test->source = $source;
        $test->manifestPath = $manifestPath;
        $test->position = $position;

        return $test;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfiguration(): ?TestConfigurationInterface
    {
        return $this->manifest instanceof TestManifest ? $this->manifest->getConfiguration() : null;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }

    public function getTarget(): ?string
    {
        return $this->manifest instanceof TestManifest ? $this->manifest->getTarget() : null;
    }

    public function getStepCount(): ?int
    {
        return $this->manifest instanceof TestManifest ? $this->manifest->getStepCount() : null;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setManifest(TestManifest $manifest): void
    {
        $this->manifest = $manifest;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $configurationData = [];

        if ($this->manifest instanceof TestManifest) {
            $configuration = $this->manifest->getConfiguration();
            $configurationData = [
                'browser' => $configuration->getBrowser(),
                'url' => $configuration->getUrl(),
            ];
        }

        return [
            'configuration' => $configurationData,
            'source' => $this->source,
            'target' => $this->getTarget(),
            'step_count' => $this->getStepCount(),
            'state' => $this->state,
            'position' => $this->position,
        ];
    }
}
