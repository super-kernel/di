<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Di\Contract\DefinitionInterface;

final readonly class NamedDefinition implements DefinitionInterface
{
    public function __construct(public string $name)
    {
    }

    public function identifier(): string
    {
        return $this->name;
    }
}
