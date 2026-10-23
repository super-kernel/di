<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use ReflectionClass;
use SuperKernel\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Exception\NotFoundException;
use Throwable;

use function class_exists;

/**
 * Defines instantiable class identifiers for constructor resolution.
 */
#[Definer]
final class ClassDefiner implements DefinerInterface
{
    public function support(string $id): bool
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

    public function get(string $id): DefinitionInterface
    {
        if (!$this->support($id)) {
            throw NotFoundException::forIdentifier($id);
        }

        /* @var class-string $id */
        return new ClassDefinition($id);
    }
}
