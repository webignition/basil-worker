<?php

declare(strict_types=1);

namespace App\Entity\Callback;

class JobTimeoutCallback extends AbstractCallbackWrapper
{
    private int $maximumDuration;

    public function __construct(int $maximumDuration)
    {
        $this->maximumDuration = $maximumDuration;

        parent::__construct(CallbackEntity::create(
            CallbackInterface::TYPE_JOB_TIMEOUT,
            [
                'maximum-duration' => $this->maximumDuration,
            ]
        ));
    }

    public function getMaximumDuration(): int
    {
        return $this->maximumDuration;
    }
}
