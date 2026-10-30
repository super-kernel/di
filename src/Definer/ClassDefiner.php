<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definer;

use SuperKernel\Attribute\Definer;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Exception\NotFoundException;

/**
 * Defines instantiable class identifiers for constructor resolution.
 */
#[Definer]
final class ClassDefiner implements DefinerInterface
{
    public function support(string $id): bool
    {
        return InstantiableClass::exists($id);
    }

    public function get(string $id): DefinitionInterface
    {
        if (!InstantiableClass::exists($id)) {
            throw NotFoundException::forIdentifier($id);
        }

        /* @var class-string $id */
        return new ClassDefinition($id);
    }
}
