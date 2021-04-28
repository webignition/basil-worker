<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;

class JobCreateRequest extends AbstractEncapsulatingRequest
{
    public const KEY_LABEL = 'label';
    public const KEY_CALLBACK_URL = 'callback-url';
    public const KEY_MAXIMUM_DURATION = 'maximum-duration-in-seconds';

    private string $label = '';
    private string $callbackUrl = '';
    private ?int $maximumDurationInSeconds;

    public function processRequest(Request $request): void
    {
        $requestData = $request->request;

        $this->label = (string) $requestData->get(self::KEY_LABEL);
        $this->callbackUrl = (string) $requestData->get(self::KEY_CALLBACK_URL);

        $maximumDurationInSeconds = null;
        if ($requestData->has(self::KEY_MAXIMUM_DURATION)) {
            $maximumDurationInRequest = $requestData->get(self::KEY_MAXIMUM_DURATION);
            if (is_int($maximumDurationInRequest) || ctype_digit($maximumDurationInRequest)) {
                $maximumDurationInSeconds = (int) $maximumDurationInRequest;
            }
        }

        $this->maximumDurationInSeconds = $maximumDurationInSeconds;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function getMaximumDurationInSeconds(): ?int
    {
        return $this->maximumDurationInSeconds;
    }
}
