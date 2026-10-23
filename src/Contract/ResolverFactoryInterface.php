<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use Psr\Container\ContainerExceptionInterface;

/**
 * Resolves supported definitions through priority-discovered, container-bound resolvers.
 */
interface ResolverFactoryInterface
{
    public function support(DefinitionInterface $definition): bool;

    /**
     * @throws ContainerExceptionInterface when resolution fails
     */
    public function resolve(DefinitionInterface $definition): mixed;
}
