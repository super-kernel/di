<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionClass;
use Throwable;

use function class_exists;

/**
 * Decides whether an identifier names an existing class that native reflection reports as instantiable.
 *
 * @internal
 */
final class InstantiableClass
{
    /**
     * Autoloading failures raised by `class_exists()` propagate; reflection failures report false.
     */
    public static function exists(string $id): bool
    {
        if (!class_exists($id)) {
            return false;
        }

        try {
            return new ReflectionClass($id)->isInstantiable();
        } catch (Throwable) {
            return false;
        }
    }
}
