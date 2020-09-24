<?php

declare(strict_types=1);

namespace App\Request;

class JobCreateRequest
{
    public const KEY_LABEL = 'label';
    public const KEY_CALLBACK_URL = 'callback-url';

    private string $label;
    private string $callbackUrl;

    /**
     * @param string $label
     * @param string $callbackUrl
     */
    public function __construct(string $label, string $callbackUrl)
    {
        $this->label = $label;
        $this->callbackUrl = $callbackUrl;
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
