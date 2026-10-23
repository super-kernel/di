<?php

declare(strict_types=1);

namespace SuperKernel\Di\Definition;

use SuperKernel\Di\Contract\DefinitionInterface;

final readonly class ProviderDefinition implements DefinitionInterface
{
    /**
     * @param class-string $className
     * @param class-string $providerClass
     */
    public function __construct(public string $className, public string $providerClass)
    {
    }

    public function identifier(): string
    {
        return $this->className;
    }
}
