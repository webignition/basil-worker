<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class JobCreateRequest
{
    public const KEY_LABEL = 'label';
    public const KEY_CALLBACK_URL = 'callback-url';

    /**
     * @var ParameterBag<mixed>
     */
    private ParameterBag $requestData;

    public function __construct(Request $request)
    {
        $this->requestData = $request->request;
    }

    public function getLabel(): string
    {
        return (string) $this->requestData->get(self::KEY_LABEL);
    }

    public function getCallbackUrl(): string
    {
        return (string) $this->requestData->get(self::KEY_CALLBACK_URL);
    }
}
