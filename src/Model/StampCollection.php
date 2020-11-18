<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Messenger\Stamp\StampInterface;

class StampCollection implements \Countable
{
    /**
     * @var StampInterface[]
     */
    private array $stamps = [];

    /**
     * @param array<mixed> $stamps
     */
    public function __construct(array $stamps = [])
    {
        foreach ($stamps as $stamp) {
            if ($stamp instanceof StampInterface) {
                $this->stamps[] = $stamp;
            }
        }
    }

    /**
     * @return StampInterface[]
     */
    public function getStamps(): array
    {
        return $this->stamps;
    }

    public function count(): int
    {
        return count($this->stamps);
    }

    public function hasStamps(): bool
    {
        return 0 !== $this->count();
    }
}
