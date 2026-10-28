<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

/**
 * Describes an instantiable class entry.
 */
final readonly class ClassDefinition implements DefinitionInterface
{
    /**
     * @param class-string $className
     */
    public function __construct(public string $className)
    {
    }

    public function identifier(): string
    {
        return $this->className;
    }
}
