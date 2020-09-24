<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class JobState extends AbstractState
{
    public static function create(string $name): self
    {
        $state = new JobState();
        $state->name = $name;

        return $state;
    }
}
