<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ClassDefinition;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function array_is_list;
use function array_keys;
use function get_debug_type;
use function is_array;
use function is_string;

/**
 * Resolves class definitions through constructor autowiring.
 */
#[Resolver(priority: 200)]
final readonly class ClassResolver implements ResolverInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function support(DefinitionInterface $definition): bool
    {
        return $definition instanceof ClassDefinition;
    }

    /**
     * @throws ContainerExceptionInterface When dispatcher lookup, construction, or dependency injection fails
     */
    public function resolve(DefinitionInterface $definition): mixed
    {
        if (!$definition instanceof ClassDefinition) {
            throw ResolutionException::unsupportedDefinition($definition);
        }

        try {
            $class = new ReflectionClass($definition->className);
        } catch (Throwable $throwable) {
            throw ResolutionException::constructionFailed($definition->className, $throwable);
        }

        $resolverFactory = $this->container->get(ResolverFactoryInterface::class);
        $arguments = $resolverFactory->resolve(new MethodDefinition($definition->className));

        if (!is_array($arguments) || !array_is_list($arguments)) {
            throw ResolutionException::invalidMethodArguments(
                $definition->className,
                '__construct',
                get_debug_type($arguments),
            );
        }

        try {
            $instance = $class->newInstanceArgs($arguments);
        } catch (Throwable $throwable) {
            throw ResolutionException::constructionFailed($definition->className, $throwable);
        }

        if ([] === $class->getProperties()) {
            return $instance;
        }

        $properties = $resolverFactory->resolve(new PropertyDefinition($definition->className));

        if (!is_array($properties)) {
            throw ResolutionException::invalidPropertyValues(
                $definition->className,
                get_debug_type($properties),
            );
        }

        foreach (array_keys($properties) as $propertyName) {
            if (!is_string($propertyName) || !$class->hasProperty($propertyName)) {
                throw ResolutionException::invalidPropertyName($definition->className, $propertyName);
            }

            $property = $class->getProperty($propertyName);

            if ($property->isStatic()) {
                throw ResolutionException::staticPropertyInjectionUnsupported(
                    $definition->className,
                    $propertyName,
                );
            }

            try {
                $property->setValue($instance, $properties[$propertyName]);
            } catch (Throwable $throwable) {
                throw ResolutionException::propertyInjectionFailed(
                    $definition->className,
                    $propertyName,
                    $throwable,
                );
            }
        }

        return $instance;
    }

}
