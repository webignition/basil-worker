<?php

declare(strict_types=1);

namespace App\Exception;

class JobCreateRequestException extends \Exception
{
    public const CODE_LABEL_MISSING = 100;
    public const CODE_CALLBACK_URL_MISSING = 200;

    private const MESSAGE_LABEL_MISSING = 'label missing';
    private const MESSAGE_CALLBACK_URL_MISSING = 'callback url missing';

    public static function createLabelMissingException(): self
    {
        return new JobCreateRequestException(
            self::MESSAGE_LABEL_MISSING,
            self::CODE_LABEL_MISSING
        );
    }

    public static function createCallbackUrlMissingException(): self
    {
        return new JobCreateRequestException(
            self::MESSAGE_CALLBACK_URL_MISSING,
            self::CODE_CALLBACK_URL_MISSING
        );
    }
}
