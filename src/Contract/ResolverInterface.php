<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use SuperKernel\Contract\ContainerInterface;

/**
 * Defines the behavior implemented by a priority-discovered resolver.
 *
 * The supplied container is the dependency-lookup boundary bound to the root container. Nested definitions are
 * submitted to the ResolverFactoryInterface dispatcher obtained from the supplied container.
 */
interface ResolverInterface
{
    public function __construct(ContainerInterface $container);

    public function support(DefinitionInterface $definition): bool;

    public function resolve(DefinitionInterface $definition): mixed;
}
