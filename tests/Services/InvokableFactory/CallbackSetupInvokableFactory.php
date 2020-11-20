<?php

declare(strict_types=1);

namespace App\Tests\Services\InvokableFactory;

use App\Entity\Callback\CallbackEntity;
use App\Entity\Callback\CallbackInterface;
use App\Services\CallbackStore;
use App\Tests\Model\EndToEndJob\Invokable;
use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\InvokableItemInterface;
use App\Tests\Model\EndToEndJob\ServiceReference;

class CallbackSetupInvokableFactory
{
    public static function setup(CallbackSetup $callbackSetup): InvokableInterface
    {
        $collection = [];

        $collection[] = self::setState(
            self::create($callbackSetup->getType(), $callbackSetup->getPayload()),
            $callbackSetup->getState()
        );

        return new InvokableCollection($collection);
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed> $payload
     *
     * @return InvokableInterface
     */
    private static function create(string $type, array $payload): InvokableItemInterface
    {
        return new Invokable(
            function (CallbackStore $callbackStore, string $type, array $payload): CallbackInterface {
                $callback = CallbackEntity::create($type, $payload);

                return $callbackStore->store($callback);
            },
            [
                new ServiceReference(CallbackStore::class),
                $type,
                $payload,
            ]
        );
    }

    /**
     * @param CallbackInterface::STATE_* $state
     *
     * @return InvokableInterface
     */
    private static function setState(InvokableItemInterface $creator, string $state): InvokableInterface
    {
        return new Invokable(
            function (CallbackStore $callbackStore, CallbackInterface $callback, string $state): CallbackInterface {
                $callback->setState($state);

                return $callbackStore->store($callback);
            },
            [
                new ServiceReference(CallbackStore::class),
                $creator,
                $state
            ]
        );
    }
}
