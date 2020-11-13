<?php

declare(strict_types=1);

namespace App\Tests\Functional;

trait TestClassServicePropertyInjectorTrait
{
    protected function injectContainerServicesIntoClassProperties()
    {
        $reflectionClass = new \ReflectionClass($this);
        $properties = $reflectionClass->getProperties(
            \ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED
        );

        $properties = array_filter($properties, function (\ReflectionProperty $property) {
            return $property->getDeclaringClass()->getName() === self::class;
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
