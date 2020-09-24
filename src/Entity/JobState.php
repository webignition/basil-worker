<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class JobState extends AbstractState
{
    public const STATE_COMPILATION_AWAITING = 'compilation-awaiting';
    public const STATE_COMPILATION_RUNNING = 'compilation-running';
    public const STATE_COMPILATION_FAILED = 'compilation-failed';
    public const EXECUTION_AWAITING = 'execution-awaiting';
    public const EXECUTION_RUNNING = 'execution-running';
    public const EXECUTION_FAILED = 'execution-failed';
    public const EXECUTION_COMPLETE = 'execution-complete';

    public static function create(string $name): self
    {
        $state = new JobState();
        $state->name = $name;

        return $state;
    }
}
