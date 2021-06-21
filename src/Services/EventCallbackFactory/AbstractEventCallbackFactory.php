<?php

declare(strict_types=1);

namespace App\Services\EventCallbackFactory;

use App\Services\EntityFactory\CallbackFactory as CallbackEntityFactory;
use webignition\BasilWorker\PersistenceBundle\Entity\Callback\CallbackInterface;

abstract class AbstractEventCallbackFactory implements EventCallbackFactoryInterface
{
    public function __construct(private CallbackEntityFactory $callbackEntityFactory)
    {
    }

    /**
     * @param CallbackInterface::TYPE_* $type
     * @param array<mixed>              $data
     */
    protected function create(string $type, array $data): CallbackInterface
    {
        return $this->callbackEntityFactory->create($type, $data);
    }
}
