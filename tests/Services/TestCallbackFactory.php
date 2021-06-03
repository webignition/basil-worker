<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\CallbackSetup;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;
use webignition\BasilWorker\PersistenceBundle\Services\Factory\CallbackFactory;

class TestCallbackFactory
{
    public function __construct(
        private CallbackFactory $bundleCallbackFactory,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function create(CallbackSetup $callbackSetup): CallbackInterface
    {
        $callback = $this->bundleCallbackFactory->create(
            $callbackSetup->getType(),
            $callbackSetup->getPayload()
        );

        $callback->setState($callbackSetup->getState());
        $this->entityManager->flush();

        return $callback;
    }
}
