<?php

/**
 * @throws ReflectionException
 */
function getClassImplements (string $class): array
{
    $reflectionClass = new ReflectionClass($class);
    $parentClasses = [];
    $currentClass = $reflectionClass;
    
    while ($currentClass->getParentClass()) {
        $currentClass = $currentClass->getParentClass();
        $name = $currentClass->getName();
        $parentClasses[$name] = $name;
    }
    
    $data = $parentClasses;
    $interfaces = $reflectionClass->getInterfaces();
    
    if (count($parentClasses) > 0) {
        foreach ($parentClasses as $parentClass) {
            $data[$parentClass] = $parentClass;
        }
    }
    
    if (count($interfaces) > 0) {
        foreach ($interfaces as $interface) {
            $data[] = $interface->getName();
        }
    }
    
    return array_values($data);
}