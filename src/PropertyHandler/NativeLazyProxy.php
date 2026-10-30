<?php

declare(strict_types=1);

namespace SuperKernel\Di\PropertyHandler;

use ReflectionClass;
use stdClass;

/**
 * Decides whether a PHP 8.4 native lazy proxy can stand in for instances of a class.
 *
 * @internal
 */
final class NativeLazyProxy
{
    /**
     * Rejects non-instantiable classes, internal classes other than `stdClass` and their subclasses, and classes
     * without stored instance properties in their hierarchy.
     *
     * @param ReflectionClass<object> $class
     */
    public static function supports(ReflectionClass $class): bool
    {
        if (!$class->isInstantiable()) {
            return false;
        }

        $hasStoredProperties = false;

        do {
            if ($class->isInternal() && stdClass::class !== $class->getName()) {
                return false;
            }

            foreach ($class->getProperties() as $property) {
                if (!$property->isStatic() && !$property->isVirtual()) {
                    $hasStoredProperties = true;
                }
            }

            $class = $class->getParentClass();
        } while (false !== $class);

        return $hasStoredProperties;
    }
}
