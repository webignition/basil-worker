<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Callback\CallbackInterface;
use App\Services\EntityFactory\CallbackFactory as CallbackEntityFactory;
use App\Tests\Model\CallbackSetup;
use Doctrine\ORM\EntityManagerInterface;

class TestCallbackFactory
{
    public function __construct(
        private CallbackEntityFactory $callbackEntityFactory,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function create(CallbackSetup $callbackSetup): CallbackInterface
    {
        $callback = $this->callbackEntityFactory->create(
            $callbackSetup->getType(),
            $callbackSetup->getPayload()
        );

        $callback->setState($callbackSetup->getState());
        $this->entityManager->flush();

        return $callback;
    }
}
