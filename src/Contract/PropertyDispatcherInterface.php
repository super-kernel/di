<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionAttribute;
use SuperKernel\Contract\PropertyContextInterface;

/**
 * Dispatches native property Attributes to their discovered handlers.
 *
 * @internal
 */
interface PropertyDispatcherInterface
{
    /**
     * @param class-string $attribute
     *
     * @throws ContainerExceptionInterface When collector or reflector lookup fails
     * @throws InvalidArgumentException    When handler mappings are invalid or duplicated
     */
    public function supports(string $attribute): bool;

    /**
     * @param ReflectionAttribute<object> $attribute
     *
     * @throws ContainerExceptionInterface When collector or reflector lookup or property handling fails
     * @throws InvalidArgumentException    When mappings are invalid or duplicated, or the Attribute has no handler
     */
    public function dispatch(PropertyContextInterface $context, ReflectionAttribute $attribute): void;
}
