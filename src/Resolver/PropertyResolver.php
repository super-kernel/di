<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\PropertyDispatcherInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use SuperKernel\Di\Property\PropertyContext;
use SuperKernel\Di\Property\PropertyDispatcher;
use Throwable;

/**
 * Dispatches exact native property Attributes to their discovered handlers in reflection order.
 */
#[Resolver(priority: 100)]
final class PropertyResolver implements ResolverInterface
{
    private PropertyDispatcherInterface $dispatcher {
        get {
            if (!isset($this->dispatcher)) {
                $this->dispatcher = new PropertyDispatcher($this->container);
            }

            return $this->dispatcher;
        }
    }

    private ReflectorInterface $reflector {
        /**
         * @throws ContainerExceptionInterface When the reflector is missing or cannot be resolved
         */
        get {
            if (!isset($this->reflector)) {
                $this->reflector = $this->container->get(ReflectorInterface::class);
            }

            return $this->reflector;
        }
    }

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function support(DefinitionInterface $definition): bool
    {
        return $definition instanceof PropertyDefinition;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ContainerExceptionInterface When reflector lookup or property resolution fails
     */
    public function resolve(DefinitionInterface $definition): array
    {
        if (!$definition instanceof PropertyDefinition) {
            throw ResolutionException::unsupportedDefinition($definition);
        }

        try {
            $reflectClass = $this->reflector->reflectClass($definition->className);
            $reflectedProperties = $reflectClass->getProperties();

            if ([] === $reflectedProperties) {
                return [];
            }

            $properties = [];

            foreach ($reflectedProperties as $property) {
                $context = new PropertyContext($reflectClass->getName(), $property);

                foreach ($property->getAttributes() as $attribute) {
                    if (!$this->dispatcher->supports($attribute->getName())) {
                        continue;
                    }

                    if ($property->isStatic()) {
                        throw ResolutionException::staticPropertyInjectionUnsupported(
                            $reflectClass->getName(),
                            $property->getName(),
                        );
                    }

                    $this->dispatcher->dispatch($context, $attribute);
                }

                if ($context->hasAssignment()) {
                    $properties[$property->getName()] = $context->assignment();
                }
            }

            return $properties;
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw ResolutionException::propertyInspectionFailed($definition->className, $throwable);
        }
    }
}
