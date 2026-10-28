<?php

declare(strict_types=1);

namespace SuperKernel\Di\Property;

use Attribute;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use SuperKernel\Attribute\PropertyHandler as PropertyHandlerAttribute;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\AttributeMetadataInterface;
use SuperKernel\Contract\PropertyAttributeHandlerInterface;
use SuperKernel\Contract\PropertyContextInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Contract\PropertyDispatcherInterface;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function array_key_exists;
use function sprintf;

/**
 * Container-owned discovery and dispatch of property Attribute handlers.
 *
 * @internal
 */
final class PropertyDispatcher implements PropertyDispatcherInterface
{
    private ReflectorInterface $reflector;

    /** @var array<class-string, PropertyAttributeHandlerInterface> */
    private array $handlers {
        /**
         * @throws ContainerExceptionInterface When the annotation collector or reflector cannot be resolved
         */
        get {
            if (!isset($this->handlers)) {
                $collector = $this->collector();
                $handlers = [];
                $handlerInstances = [];
                $registered = [];

                foreach ($collector->getClassesWithAttribute(
                    PropertyHandlerAttribute::class,
                ) as $className => $attributes) {
                    foreach ($attributes as $attribute) {
                        $propertyAttribute = self::propertyAttribute($attribute);
                        $this->validatePropertyAttribute($propertyAttribute, $className);

                        if (array_key_exists($propertyAttribute, $registered)) {
                            throw new InvalidArgumentException(sprintf(
                                'Property Attribute %s is handled by both %s and %s.',
                                $propertyAttribute,
                                $registered[$propertyAttribute],
                                $className,
                            ));
                        }

                        if (!array_key_exists($className, $handlerInstances)) {
                            $handlerInstances[$className] = $this->createHandler($className);
                        }

                        $handlers[$propertyAttribute] = $handlerInstances[$className];
                        $registered[$propertyAttribute] = $className;
                    }
                }

                $this->handlers = $handlers;
            }

            return $this->handlers;
        }
    }

    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function supports(string $attribute): bool
    {
        return array_key_exists($attribute, $this->handlers);
    }

    public function dispatch(PropertyContextInterface $context, ReflectionAttribute $attribute): void
    {
        $attributeName = $attribute->getName();

        if (!array_key_exists($attributeName, $this->handlers)) {
            throw new InvalidArgumentException(sprintf(
                'Property Attribute %s has no registered handler.',
                $attributeName,
            ));
        }

        $handler = $this->handlers[$attributeName];

        try {
            $handler->handle($context, $attribute->newInstance());
        } catch (Throwable $throwable) {
            if ($throwable instanceof ContainerExceptionInterface) {
                throw $throwable;
            }

            throw ResolutionException::propertyHandlerFailed(
                $handler::class,
                $attributeName,
                $context->className(),
                $context->property()->getName(),
                $throwable,
            );
        }
    }

    /**
     * @throws ContainerExceptionInterface When the reflector is missing or cannot be resolved
     */
    private function reflector(): ReflectorInterface
    {
        if (!isset($this->reflector)) {
            $this->reflector = $this->container->get(ReflectorInterface::class);
        }

        return $this->reflector;
    }

    /**
     * @param class-string $className
     *
     * @throws ContainerExceptionInterface When the reflector cannot be resolved
     * @throws InvalidArgumentException    When the handler cannot be reflected, validated, or constructed
     */
    private function createHandler(string $className): PropertyAttributeHandlerInterface
    {
        try {
            $reflection = $this->reflector()->reflectClass($className);
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Unable to reflect annotated property handler %s.',
                $className,
            ), 0, $throwable);
        }

        if (
            !$reflection->isInstantiable()
            || !$reflection->implementsInterface(PropertyAttributeHandlerInterface::class)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Annotated property handler %s must be an instantiable implementation of %s.',
                $className,
                PropertyAttributeHandlerInterface::class,
            ));
        }

        try {
            $handler = $reflection->newInstance($this->container);
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Unable to create annotated property handler %s with the root container.',
                $className,
            ), 0, $throwable);
        }

        if (!$handler instanceof PropertyAttributeHandlerInterface) {
            throw new InvalidArgumentException(sprintf(
                'Annotated property handler %s must implement %s.',
                $className,
                PropertyAttributeHandlerInterface::class,
            ));
        }

        return $handler;
    }

    /**
     * @param AttributeMetadataInterface<PropertyHandlerAttribute> $attribute
     *
     * @return class-string
     */
    private static function propertyAttribute(AttributeMetadataInterface $attribute): string
    {
        return $attribute->attribute()->attribute;
    }

    /**
     * @param class-string $attribute
     * @param class-string $handler
     *
     * @throws ContainerExceptionInterface When the reflector cannot be resolved
     * @throws InvalidArgumentException    When the mapped class is not a valid instantiable property Attribute
     */
    private function validatePropertyAttribute(string $attribute, string $handler): void
    {
        try {
            $reflection = $this->reflector()->reflectClass($attribute);
            $nativeAttributes = $reflection->getAttributes(Attribute::class);
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Property handler %s maps an invalid Attribute class %s.',
                $handler,
                $attribute,
            ), 0, $throwable);
        }

        if ([] === $nativeAttributes || !$reflection->isInstantiable()) {
            throw new InvalidArgumentException(sprintf(
                'Property handler %s must map an instantiable native Attribute class; %s is not one.',
                $handler,
                $attribute,
            ));
        }

        try {
            $flags = $nativeAttributes[0]->newInstance()->flags;
        } catch (Throwable $throwable) {
            throw new InvalidArgumentException(sprintf(
                'Unable to inspect native Attribute targets for %s mapped by %s.',
                $attribute,
                $handler,
            ), 0, $throwable);
        }

        if (0 === ($flags & Attribute::TARGET_PROPERTY)) {
            throw new InvalidArgumentException(sprintf(
                'Property handler %s maps %s, which does not target properties.',
                $handler,
                $attribute,
            ));
        }
    }

    /** @throws ContainerExceptionInterface When the annotation collector cannot be resolved */
    private function collector(): AnnotationCollectorInterface
    {
        return $this->container->get(AnnotationCollectorInterface::class);
    }

}
