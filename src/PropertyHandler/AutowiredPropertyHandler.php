<?php

declare(strict_types=1);

namespace SuperKernel\Di\PropertyHandler;

use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
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
        $property = $context->property();
        $identifier = $attribute->identifier;

        if (null === $identifier) {
            $type = $property->getType();
            $memberTypes = $type instanceof ReflectionUnionType ? $type->getTypes() : [
                $type,
            ];
            $candidates = [];

            foreach ($memberTypes as $memberType) {
                if (!$memberType instanceof ReflectionNamedType || $memberType->isBuiltin()) {
                    continue;
                }

                $candidate = match ($memberType->getName()) {
                    'self',
                    'static' => $class->getName(),
                    'parent' => $class->getParentClass()?->getName(),
                    default  => $memberType->getName(),
                };

                if (null !== $candidate && (class_exists($candidate) || interface_exists($candidate))) {
                    $candidates[] = $candidate;
                }
            }

            $candidates = array_values(array_unique($candidates));
            $identifier = 1 === count($candidates) ? $candidates[0] : null;
        }

        if (null === $identifier) {
            if (!$property->hasDefaultValue()) {
                throw ResolutionException::unresolvableProperty(
                    $class->getName(),
                    $property->getName(),
                    null === $property->getType() ? null : (string)$property->getType(),
                );
            }

            $context->assign($property->getDefaultValue());

            return;
        }

        $proxyClassName = null;

        if (class_exists($identifier)) {
            $reflection = $this->reflector->reflectClass($identifier);

            if ($reflection->isInstantiable()) {
                $proxyClassName = $reflection->getName();
            }
        }

        if (null === $proxyClassName) {
            $definitionFactory = $this->container->get(DefinitionFactoryInterface::class);
            $definition = $definitionFactory->get($identifier);

            if ($definition instanceof ProviderDefinition) {
                $proxyClassName = $definition->providerClass;
            }
        }

        if (null === $proxyClassName) {
            $context->assign($this->container->get($identifier));

            return;
        }

        $proxyClass = $this->reflector->reflectClass($proxyClassName);

        if (!NativeLazyProxy::supports($proxyClass)) {
            $context->assign($this->container->get($identifier));

            return;
        }

        try {
            $proxy = $proxyClass->newLazyProxy(function () use ($identifier, $proxyClass, $proxyClassName): object {
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

        $context->assign($proxy);
    }
}
