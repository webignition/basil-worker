<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use webignition\BasilWorker\PersistenceBundle\Entity\Test;

abstract class AbstractTestEvent extends Event
{
    private Test $test;

    public function __construct(Test $test)
    {
        $this->test = $test;
    }

    public function getTest(): Test
    {
        return $this->test;
    }
}
