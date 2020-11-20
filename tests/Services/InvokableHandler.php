<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Model\EndToEndJob\InvokableCollection;
use App\Tests\Model\EndToEndJob\InvokableInterface;
use App\Tests\Model\EndToEndJob\InvokableItemInterface;
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
        $this->resolveInvokableArguments($invokable);

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

    private function resolveInvokableArguments(InvokableInterface $invokable): InvokableInterface
    {
        if ($invokable instanceof InvokableCollection) {
            foreach ($invokable->getItems() as $itemIndex => $item) {
                $resolvedItem = $this->resolveInvokableArguments($item);
                $invokable->setItem($itemIndex, $resolvedItem);
            }
        }

        if ($invokable instanceof InvokableItemInterface) {
            foreach ($invokable->getArguments() as $argumentIndex => $argument) {
                if ($argument instanceof InvokableInterface) {
                    $argumentWithInjectedServices = $this->injectServicesIntoInvokable($argument);


                    $invokable->setArgument($argumentIndex, $argumentWithInjectedServices());
                }
            }
        }

        return $invokable;
    }
}
