<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;

final readonly class FirstResolver implements ResolverInterface
{
    public function __construct(public ContainerInterface $container)
    {
    }

    public function support(DefinitionInterface $definition): bool
    {
        return $definition instanceof NamedDefinition;
    }

    public function resolve(DefinitionInterface $definition): string
    {
        return 'first';
    }
}
