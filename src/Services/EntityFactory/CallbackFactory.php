<?php

declare(strict_types=1);

namespace App\Services\EntityFactory;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;

class CallbackFactory extends AbstractEntityFactory
{
    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed>              $payload
     */
    public function create(string $type, array $payload): CallbackInterface
    {
        $callback = CallbackEntity::create($type, $payload);

        $this->persist($callback);

        return $callback;
    }
}
