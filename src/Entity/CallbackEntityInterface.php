<?php

declare(strict_types=1);

namespace App\Entity;

interface CallbackEntityInterface
{
    public const STATE_AWAITING = 'awaiting';
    public const STATE_QUEUED = 'queued';
    public const STATE_SENDING = 'sending';
    public const STATE_FAILED = 'failed';
    public const STATE_COMPLETE = 'complete';

    public const TYPE_COMPILE_FAILURE = 'compile-failure';
    public const TYPE_EXECUTE_DOCUMENT_RECEIVED = 'execute-document-received';

    public function getId(): ?int;

    /**
     * @return CallbackEntityInterface::STATE_*
     */
    public function getState(): string;

    /**
     * @param CallbackEntityInterface::STATE_* $state
     */
    public function setState(string $state): void;

    public function getRetryCount(): int;
    public function getType(): string;

    /**
     * @return array<mixed>
     */
    public function getPayload(): array;
    public function incrementRetryCount(): void;
}
