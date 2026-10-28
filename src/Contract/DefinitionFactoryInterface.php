<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use Psr\Container\ContainerExceptionInterface;

/**
 * Dispatches an identifier to the highest-priority supporting definer.
 *
 * Direct callers may pass a returned definition to ResolverFactoryInterface::resolve() when they own the resolved
 * value. They must handle an unsupported identifier returning null.
 */
interface DefinitionFactoryInterface
{
    /**
     * @throws ContainerExceptionInterface when definer dispatch fails
     */
    public function get(string $id): ?DefinitionInterface;
}
