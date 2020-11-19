<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\BackoffStrategy\BackoffStrategyInterface;
use App\Model\BackoffStrategy\ExponentialBackoffStrategy;
use App\Model\BackoffStrategy\FixedBackoffStrategy;
use Psr\Http\Message\ResponseInterface;

class BackoffStrategyFactory
{
    private const MILLISECONDS_PER_SECOND = 1000;

    /**
     * @param object $context
     *
     * @return BackoffStrategyInterface
     */
    public function create(object $context): BackoffStrategyInterface
    {
        if ($context instanceof ResponseInterface) {
            return $this->createForHttpResponse($context);
        }

        return new ExponentialBackoffStrategy();
    }

    private function createForHttpResponse(ResponseInterface $response): BackoffStrategyInterface
    {
        $retryAfterHeaderLines = $response->getHeader('retry-after');
        $lastRetryAfterValue = array_pop($retryAfterHeaderLines);

        if (null === $lastRetryAfterValue) {
            return new ExponentialBackoffStrategy();
        }

        if (ctype_digit($lastRetryAfterValue)) {
            $lastRetryAfterValue = (int) $lastRetryAfterValue;
            if ($lastRetryAfterValue > 0) {
                return new FixedBackoffStrategy($lastRetryAfterValue * self::MILLISECONDS_PER_SECOND);
            }
        }

        return new ExponentialBackoffStrategy();
    }
}
