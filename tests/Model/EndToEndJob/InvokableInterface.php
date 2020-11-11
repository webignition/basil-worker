<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

interface InvokableInterface
{
    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function __invoke(...$args);
}
