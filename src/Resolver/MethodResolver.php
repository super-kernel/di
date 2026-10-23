<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use SuperKernel\Attribute\Resolver;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Exception\ResolutionException;
use Throwable;

use function array_key_exists;
use function array_unique;
use function array_values;
use function count;

/**
 * Resolves the ordered arguments required by a class method.
 */
#[Resolver(priority: 0)]
final readonly class MethodResolver implements ResolverInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function support(DefinitionInterface $definition): bool
    {
        return $definition instanceof MethodDefinition;
    }

    /**
     * @return list<mixed>
     *
     * @throws ContainerExceptionInterface
     */
    public function resolve(DefinitionInterface $definition): array
    {
        if (!$definition instanceof MethodDefinition) {
            throw ResolutionException::unsupportedDefinition($definition);
        }

        try {
            $class = new ReflectionClass($definition->className);
            $method = $this->method($class, $definition);
        } catch (ContainerExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw ResolutionException::methodInspectionFailed(
                $definition->className,
                $definition->methodName,
                $throwable,
            );
        }

        if (null === $method) {
            return [];
        }

        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            if ($parameter->isPassedByReference()) {
                throw ResolutionException::referenceParameterUnsupported(
                    $class->getName(),
                    $method->getName(),
                    $parameter->getName(),
                );
            }

            $arguments[] = $this->resolveParameter($definition, $class, $method, $parameter);
        }

        return $arguments;
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function method(
        ReflectionClass  $class,
        MethodDefinition $definition,
    ): ?ReflectionMethod
    {
        if ('__construct' === $definition->methodName) {
            return $class->getConstructor();
        }

        if (!$class->hasMethod($definition->methodName)) {
            throw ResolutionException::methodNotFound(
                $class->getName(),
                $definition->methodName,
            );
        }

        return $class->getMethod($definition->methodName);
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveParameter(
        MethodDefinition    $definition,
        ReflectionClass     $class,
        ReflectionMethod    $method,
        ReflectionParameter $parameter,
    ): mixed
    {
        if (array_key_exists($parameter->getName(), $definition->parameters)) {
            return $definition->parameters[$parameter->getName()];
        }

        if (array_key_exists($parameter->getPosition(), $definition->parameters)) {
            return $definition->parameters[$parameter->getPosition()];
        }

        $type = $parameter->getType();
        $candidates = $this->dependencyNames($class, $type);

        $available = [];

        foreach ($candidates as $candidate) {
            if ($this->container->has($candidate)) {
                $available[] = $candidate;
            }
        }

        $availableCount = count($available);

        if (1 === $availableCount) {
            return $this->container->get($available[0]);
        }

        if (1 < $availableCount) {
            throw ResolutionException::ambiguousParameter(
                $class->getName(),
                $method->getName(),
                $parameter->getName(),
                $available,
            );
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if (true === $type?->allowsNull()) {
            return null;
        }

        throw ResolutionException::unresolvableParameter(
            $class->getName(),
            $method->getName(),
            $parameter->getName(),
            null === $type ? null : (string)$type,
        );
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return list<class-string>
     */
    private function dependencyNames(ReflectionClass $class, ?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            $candidate = $this->dependencyName($class, $type);

            return null === $candidate ? [] : [
                $candidate,
            ];
        }

        if (!$type instanceof ReflectionUnionType) {
            return [];
        }

        $candidates = [];

        foreach ($type->getTypes() as $unionType) {
            if (!$unionType instanceof ReflectionNamedType) {
                continue;
            }

            $candidate = $this->dependencyName($class, $unionType);

            if (null !== $candidate) {
                $candidates[] = $candidate;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return class-string|null
     */
    private function dependencyName(ReflectionClass $class, ReflectionNamedType $type): ?string
    {
        if ($type->isBuiltin()) {
            return null;
        }

        return match ($type->getName()) {
            'self',
            'static' => $class->getName(),
            'parent' => $class->getParentClass()?->getName() ?: null,
            default  => $type->getName(),
        };
    }
}
