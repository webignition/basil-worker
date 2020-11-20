<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\EndToEndJob\InvokableInterface;
use Psr\Container\ContainerInterface;

class InvokableHandler
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param InvokableInterface $invokable
     *
     * @return mixed
     */
    public function invoke(InvokableInterface $invokable)
    {
        $this->injectServicesIntoInvokable($invokable);

        return $invokable();
    }

    private function injectServicesIntoInvokable(InvokableInterface $invokable): InvokableInterface
    {
        foreach ($invokable->getServiceReferences() as $serviceReference) {
            $service = $this->container->get($serviceReference->getId());
            if (null !== $service) {
                $invokable->replaceServiceReference($serviceReference, $service);
            }
        }

        return $invokable;
    }
}
