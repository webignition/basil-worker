<?php

declare(strict_types=1);

namespace App\Tests;

trait TestClassServicePropertyInjectorTrait
{
    protected function injectContainerServicesIntoClassProperties(): void
    {
        $reflectionClass = new \ReflectionClass($this);
        $properties = $reflectionClass->getProperties(
            \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED
        );

        $properties = array_filter($properties, function (\ReflectionProperty $property) {
            return $property->getType() instanceof \ReflectionNamedType;
        });

        foreach ($properties as $property) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeClass = $type->getName();
                $propertyName = $property->getName();

                if (self::$container->has($typeClass)) {
                    $service = self::$container->get($typeClass);

                    if ($service instanceof $typeClass) {
                        $this->{$propertyName} = $service;
                    }
                }
            }
        }
    }
}
