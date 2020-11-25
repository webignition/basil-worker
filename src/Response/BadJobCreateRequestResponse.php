<?php

declare(strict_types=1);

namespace App\Response;

class BadJobCreateRequestResponse extends ErrorResponse
{
    private const TYPE = 'job-create-request';

    private const CODE_LABEL_MISSING = 100;
    private const CODE_CALLBACK_URL_MISSING = 200;
    private const CODE_JOB_ALREADY_EXISTS = 300;

    private const MESSAGE_LABEL_MISSING = 'label missing';
    private const MESSAGE_CALLBACK_URL_MISSING = 'callback url missing';
    private const MESSAGE_JOB_ALREADY_EXISTS = 'job already exists';

    public function __construct(string $message, int $code, int $status = self::HTTP_BAD_REQUEST)
    {
        parent::__construct(self::TYPE, $message, $code, $status);
    }

    public static function createLabelMissingResponse(): self
    {
        return new BadJobCreateRequestResponse(
            self::MESSAGE_LABEL_MISSING,
            self::CODE_LABEL_MISSING
        );
    }

    public static function createCallbackUrlMissingResponse(): self
    {
        return new BadJobCreateRequestResponse(
            self::MESSAGE_CALLBACK_URL_MISSING,
            self::CODE_CALLBACK_URL_MISSING
        );
    }

    public static function createJobAlreadyExistsResponse(): self
    {
        return new BadJobCreateRequestResponse(
            self::MESSAGE_JOB_ALREADY_EXISTS,
            self::CODE_JOB_ALREADY_EXISTS
        );
    }
}
