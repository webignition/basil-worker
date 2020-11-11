<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

interface InvokableItemInterface extends InvokableInterface
{
    /**
     * @return array<mixed>
     */
    public function getArguments(): array;
}
