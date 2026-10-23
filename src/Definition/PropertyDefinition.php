<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

/**
 * Describes the injectable properties declared by one class.
 */
final readonly class PropertyDefinition implements DefinitionInterface
{
    /**
     * @param class-string $className
     */
    public function __construct(public string $className)
    {
    }

    public function identifier(): string
    {
        return self::class . ':' . $this->className;
    }
}
