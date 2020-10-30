<?php

declare(strict_types=1);

namespace App\Event;

class TestExecuteCompleteEvent extends AbstractTestEvent
{
    public const NAME = 'worker.test.execute.complete';
}
