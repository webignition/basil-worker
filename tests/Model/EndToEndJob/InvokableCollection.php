<?php

declare(strict_types=1);

namespace App\Tests\Model\EndToEndJob;

class InvokableCollection implements InvokableInterface
{
    /**
     * @var InvokableItemInterface
     */
    private array $items = [];

    public function __construct(array $items)
    {
        foreach ($items as $invokable) {
            if ($invokable instanceof InvokableItemInterface) {
                $this->items[] = $invokable;
            }
        }
    }

    public function __invoke(...$args)
    {
        $return = null;

        foreach ($this->items as $invokable) {
            $return = $invokable(...$args);
        }

        return $return;
    }

    public function getServiceReferences(): array
    {
        return [];
    }

    public function replaceServiceReference(ServiceReference $serviceReference, object $service): void
    {
        foreach ($this->items as $invokable) {
            $invokable->replaceServiceReference($serviceReference, $service);
        }
    }
}
