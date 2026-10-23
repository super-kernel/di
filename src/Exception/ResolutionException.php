<?php

declare(strict_types=1);

namespace SuperKernel\Di\Exception;

use SuperKernel\Di\Contract\DefinitionInterface;
use Throwable;

use function get_debug_type;
use function implode;
use function sprintf;

final class ResolutionException extends ContainerException
{
    public static function definitionLookupFailed(string $identifier, Throwable $previous): self
    {
        return new self(sprintf('Unable to obtain a definition for container entry "%s".', $identifier), 0, $previous);
    }

    public static function resolverDispatchFailed(DefinitionInterface $definition, Throwable $previous): self
    {
        return new self(
            sprintf('Unable to resolve definition "%s".', get_debug_type($definition)),
            0,
            $previous,
        );
    }

    public static function unsupportedDefinition(DefinitionInterface $definition): self
    {
        return new self(sprintf('No resolver supports definition "%s".', get_debug_type($definition)));
    }

    /**
     * @param class-string $identifier
     */
    public static function entryTypeMismatch(string $identifier, string $actualType): self
    {
        return new self(sprintf(
            'Container entry "%s" resolved to %s instead of an instance of %s.',
            $identifier,
            $actualType,
            $identifier,
        ));
    }

    public static function constructionFailed(string $class, Throwable $previous): self
    {
        return new self(sprintf('Unable to construct container entry "%s".', $class), 0, $previous);
    }

    /**
     * @param class-string $factoryClass
     * @param class-string $className
     */
    public static function factoryInvocationFailed(
        string    $factoryClass,
        string    $className,
        Throwable $previous,
    ): self
    {
        return new self(sprintf(
            'Unable to invoke factory "%s" for container entry "%s".',
            $factoryClass,
            $className,
        ), 0, $previous);
    }

    /**
     * @param class-string $providerClass
     * @param class-string $className
     */
    public static function providerResolutionFailed(
        string    $providerClass,
        string    $className,
        Throwable $previous,
    ): self
    {
        return new self(sprintf(
            'Unable to resolve provider "%s" for container entry "%s".',
            $providerClass,
            $className,
        ), 0, $previous);
    }

    public static function methodNotFound(string $class, string $method): self
    {
        return new self(sprintf('Method %s::%s does not exist.', $class, $method));
    }

    public static function methodInspectionFailed(string $class, string $method, Throwable $previous): self
    {
        return new self(sprintf('Unable to inspect method %s::%s.', $class, $method), 0, $previous);
    }

    public static function invalidMethodArguments(string $class, string $method, string $type): self
    {
        return new self(sprintf(
            'Resolving method %s::%s returned %s instead of an argument list.',
            $class,
            $method,
            $type,
        ));
    }

    public static function propertyInspectionFailed(string $class, Throwable $previous): self
    {
        return new self(sprintf('Unable to inspect injectable properties on %s.', $class), 0, $previous);
    }

    public static function invalidPropertyValues(string $class, string $type): self
    {
        return new self(sprintf(
            'Resolving properties on %s returned %s instead of a property-value map.',
            $class,
            $type,
        ));
    }

    public static function invalidPropertyName(string $class, mixed $property): self
    {
        return new self(sprintf(
            'Resolving properties on %s returned invalid property key %s.',
            $class,
            get_debug_type($property),
        ));
    }

    public static function staticPropertyInjectionUnsupported(string $class, string $property): self
    {
        return new self(sprintf('Static property %s::$%s cannot be injected.', $class, $property));
    }

    public static function unresolvableProperty(
        string  $class,
        string  $property,
        ?string $type,
    ): self
    {
        return new self(sprintf(
            'Unable to resolve property %s::$%s%s.',
            $class,
            $property,
            null === $type ? '' : sprintf(' of type %s', $type),
        ));
    }

    public static function propertyInjectionFailed(
        string    $class,
        string    $property,
        Throwable $previous,
    ): self
    {
        return new self(sprintf('Unable to inject property %s::$%s.', $class, $property), 0, $previous);
    }

    /**
     * @param class-string $handler
     * @param class-string $attribute
     */
    public static function propertyHandlerFailed(
        string    $handler,
        string    $attribute,
        string    $class,
        string    $property,
        Throwable $previous,
    ): self
    {
        return new self(sprintf(
            'Property handler %s failed while processing %s on %s::$%s.',
            $handler,
            $attribute,
            $class,
            $property,
        ), 0, $previous);
    }

    public static function lazyPropertyUnsupported(
        string $class,
        string $property,
        string $identifier,
    ): self
    {
        return new self(sprintf(
            'Property %s::$%s cannot lazily resolve %s because no concrete proxy class is available.',
            $class,
            $property,
            $identifier,
        ));
    }

    public static function lazyPropertyCreationFailed(
        string    $class,
        string    $property,
        string    $identifier,
        Throwable $previous,
    ): self
    {
        return new self(sprintf(
            'Unable to create a lazy proxy for property %s::$%s using %s.',
            $class,
            $property,
            $identifier,
        ), 0, $previous);
    }

    /**
     * @param class-string $expectedClass
     */
    public static function lazyPropertyTypeMismatch(
        string $identifier,
        string $expectedClass,
        string $actualType,
    ): self
    {
        return new self(sprintf(
            'Lazy container entry %s returned %s instead of an instance of %s.',
            $identifier,
            $actualType,
            $expectedClass,
        ));
    }

    public static function referenceParameterUnsupported(string $class, string $method, string $parameter): self
    {
        return new self(sprintf(
            'Method parameter %s::%s($%s) cannot be resolved because references are unsupported.',
            $class,
            $method,
            $parameter,
        ));
    }

    public static function unresolvableParameter(
        string  $class,
        string  $method,
        string  $parameter,
        ?string $type,
    ): self
    {
        return new self(sprintf(
            'Unable to resolve method parameter %s::%s($%s)%s.',
            $class,
            $method,
            $parameter,
            null === $type ? '' : sprintf(' of type %s', $type),
        ));
    }

    /**
     * @param list<string> $candidates
     */
    public static function ambiguousParameter(
        string $class,
        string $method,
        string $parameter,
        array  $candidates,
    ): self
    {
        return new self(sprintf(
            'Method parameter %s::%s($%s) is ambiguous: %s are available.',
            $class,
            $method,
            $parameter,
            implode(', ', $candidates),
        ));
    }
}
