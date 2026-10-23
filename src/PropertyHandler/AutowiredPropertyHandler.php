<?php

declare(strict_types=1);

namespace SuperKernel\Di\PropertyHandler;

use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use stdClass;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Attribute\PropertyHandler;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\PropertyAttributeHandlerInterface;
use SuperKernel\Contract\PropertyContextInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Definition\ProviderDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function get_debug_type;
use function interface_exists;
use function is_object;
use function sprintf;

#[PropertyHandler(Autowired::class)]
final class AutowiredPropertyHandler implements PropertyAttributeHandlerInterface
{
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

    /**
     * @throws ContainerExceptionInterface When the reflector, property value, or lazy proxy cannot be resolved
     * @throws InvalidArgumentException    When the attribute is not an Autowired instance
     * @throws LogicException              When the context already contains an assignment
     * @throws ReflectionException         When the context class or a lazy proxy candidate cannot be reflected
     */
    public function handle(PropertyContextInterface $context, object $attribute): void
    {
        if (!$attribute instanceof Autowired) {
            throw new InvalidArgumentException(sprintf(
                '%s only handles %s instances; received %s.',
                self::class,
                Autowired::class,
                get_debug_type($attribute),
            ));
        }

        $class = $this->reflector->reflectClass($context->className());
        $context->assign($this->resolveProperty($class, $context->property(), $attribute));
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @throws ContainerExceptionInterface When property resolution or lazy proxy creation fails
     * @throws ReflectionException         When a lazy proxy candidate class cannot be reflected
     */
    private function resolveProperty(
        ReflectionClass    $class,
        ReflectionProperty $property,
        Autowired          $autowired,
    ): mixed
    {
        $identifier = $autowired->identifier ?? $this->propertyIdentifier($class, $property->getType());

        if (null !== $identifier) {
            return $this->lazyProxy($class, $property, $identifier);
        }

        if ($property->hasDefaultValue()) {
            return $property->getDefaultValue();
        }

        throw ResolutionException::unresolvableProperty(
            $class->getName(),
            $property->getName(),
            null === $property->getType() ? null : (string)$property->getType(),
        );
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return class-string|null
     */
    private function propertyIdentifier(ReflectionClass $class, ?ReflectionType $type): ?string
    {
        $candidates = [];

        if ($type instanceof ReflectionNamedType) {
            $candidate = $this->typeIdentifier($class, $type);

            if (null !== $candidate) {
                $candidates[] = $candidate;
            }
        } elseif ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if (!$unionType instanceof ReflectionNamedType) {
                    continue;
                }

                $candidate = $this->typeIdentifier($class, $unionType);

                if (null !== $candidate) {
                    $candidates[] = $candidate;
                }
            }
        }

        $candidates = array_values(array_unique($candidates));

        return 1 === count($candidates) ? $candidates[0] : null;
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return class-string|null
     */
    private function typeIdentifier(ReflectionClass $class, ReflectionNamedType $type): ?string
    {
        if ($type->isBuiltin()) {
            return null;
        }

        $identifier = match ($type->getName()) {
            'self',
            'static' => $class->getName(),
            'parent' => $class->getParentClass()?->getName(),
            default  => $type->getName(),
        };

        if (null === $identifier || (!class_exists($identifier) && !interface_exists($identifier))) {
            return null;
        }

        return $identifier;
    }

    /**
     * @param ReflectionClass<object> $class
     * @param class-string            $identifier
     *
     * @throws ContainerExceptionInterface When proxy class resolution or lazy proxy creation fails
     * @throws ReflectionException         When a lazy proxy candidate class cannot be reflected
     */
    private function lazyProxy(
        ReflectionClass    $class,
        ReflectionProperty $property,
        string             $identifier,
    ): object
    {
        $proxyClassName = $this->proxyClassName($identifier);

        if (null === $proxyClassName) {
            return $this->container->get($identifier);
        }

        $proxyClass = $this->reflector->reflectClass($proxyClassName);

        if (!self::supportsLazyProxy($proxyClass)) {
            return $this->container->get($identifier);
        }

        try {
            return $proxyClass->newLazyProxy(function () use ($identifier, $proxyClass, $proxyClassName): object {
                $instance = $this->container->get($identifier);

                if (is_object($instance) && $proxyClass->isInstance($instance)) {
                    return $instance;
                }

                throw ResolutionException::lazyPropertyTypeMismatch(
                    $identifier,
                    $proxyClassName,
                    get_debug_type($instance),
                );
            });
        } catch (Throwable $throwable) {
            throw ResolutionException::lazyPropertyCreationFailed(
                $class->getName(),
                $property->getName(),
                $identifier,
                $throwable,
            );
        }
    }

    /**
     * @param class-string $identifier
     *
     * @return class-string|null
     *
     * @throws ContainerExceptionInterface When reflection services or definition lookup fail
     * @throws ReflectionException         When the identifier class cannot be reflected
     */
    private function proxyClassName(string $identifier): ?string
    {
        if (class_exists($identifier)) {
            $reflection = $this->reflector->reflectClass($identifier);

            if ($reflection->isInstantiable()) {
                return $reflection->getName();
            }
        }

        $definitionFactory = $this->container->get(DefinitionFactoryInterface::class);
        $definition = $definitionFactory->get($identifier);

        if ($definition instanceof ProviderDefinition) {
            return $definition->providerClass;
        }

        return null;
    }

    /** @param ReflectionClass<object> $class */
    private static function supportsLazyProxy(ReflectionClass $class): bool
    {
        if (!$class->isInstantiable()) {
            return false;
        }

        $hasStoredProperties = false;

        do {
            if ($class->isInternal() && stdClass::class !== $class->getName()) {
                return false;
            }

            foreach ($class->getProperties() as $property) {
                if (!$property->isStatic() && !$property->isVirtual()) {
                    $hasStoredProperties = true;
                }
            }

            $class = $class->getParentClass();
        } while (false !== $class);

        return $hasStoredProperties;
    }
}
