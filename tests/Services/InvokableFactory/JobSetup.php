<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Job;

class JobSetup
{
    private string $label;
    private string $callbackUrl;

    /**
     * @var string[]
     */
    private ?array $sources;

    /**
     * @var Job::STATE_*
     */
    private string $state;

    public function __construct()
    {
        $this->label = md5('label content');
        $this->callbackUrl = 'http://example.com/callback';
        $this->sources = null;
        $this->state = Job::STATE_COMPILATION_AWAITING;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    /**
     * @return string[]
     */
    public function getSources(): ?array
    {
        return $this->sources;
    }

    /**
     * @return Job::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    public function withLabel(string $label): self
    {
        $new = clone $this;
        $new->label = $label;

        return $new;
    }

    public function withCallbackUrl(string $callbackUrl): self
    {
        $new = clone $this;
        $new->callbackUrl = $callbackUrl;

        return $new;
    }

    /**
     * @param string[] $sources
     *
     * @return $this
     */
    public function withSources(array $sources): self
    {
        $new = clone $this;
        $new->sources = $sources;

        return $new;
    }

    /**
     * @param Job::STATE_* $state
     *
     * @return $this
     */
    public function withState(string $state): self
    {
        $new = clone $this;
        $new->state = $state;

        return $new;
    }
}
