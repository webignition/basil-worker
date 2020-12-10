<?php

declare(strict_types=1);

namespace App\Message;

class ExecuteTest
{
    private int $testId;

    public function __construct(int $testId)
    {
        $this->testId = $testId;
    }

    public function getTestId(): int
    {
        return $this->testId;
    }
}
