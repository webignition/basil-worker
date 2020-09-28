<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class TestState extends AbstractState implements \JsonSerializable
{
    public static function create(string $name): self
    {
        $state = new TestState();
        $state->name = $name;

        return $state;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
