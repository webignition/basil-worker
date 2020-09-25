<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;

class JobCreateRequest extends AbstractEncapsulatingRequest
{
    public const KEY_LABEL = 'label';
    public const KEY_CALLBACK_URL = 'callback-url';

    private string $label = '';
    private string $callbackUrl = '';

    public function processRequest(Request $request): void
    {
        $requestData = $request->request;

        $this->label = (string) $requestData->get(self::KEY_LABEL);
        $this->callbackUrl = (string) $requestData->get(self::KEY_CALLBACK_URL);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }
}
