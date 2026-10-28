<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

final readonly class FactoryDefinition implements DefinitionInterface
{
    /**
     * @param class-string $className
     * @param class-string $factoryClass
     */
    public function __construct(public string $className, public string $factoryClass)
    {
    }

    public function identifier(): string
    {
        return $this->className;
    }
}
