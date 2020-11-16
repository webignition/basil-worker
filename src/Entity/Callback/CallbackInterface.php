<?php

declare(strict_types=1);

namespace App\Entity\Callback;

interface CallbackInterface
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_QUEUED = 'queued';
    public const STATE_SENDING = 'sending';
    public const STATE_FAILED = 'failed';
    public const STATE_COMPLETE = 'complete';

    public const TYPE_COMPILE_FAILURE = 'compile-failure';
    public const TYPE_EXECUTE_DOCUMENT_RECEIVED = 'execute-document-received';

    public function getId(): ?int;
    public function getEntity(): CallbackEntity;

    /**
     * @return CallbackInterface::STATE_*
     */
    public function getState(): string;

    /**
     * @param CallbackInterface::STATE_* $state
     */
    public function setState(string $state): void;

    public function getRetryCount(): int;
    public function getType(): string;

    /**
     * @return array<mixed>
     */
    public function getPayload(): array;
    public function incrementRetryCount(): void;
    public function hasReachedRetryLimit(int $limit): bool;
}
