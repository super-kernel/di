<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionNamedType;
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

            if ('__construct' === $definition->methodName) {
                $method = $class->getConstructor();
            } elseif ($class->hasMethod($definition->methodName)) {
                $method = $class->getMethod($definition->methodName);
            } else {
                throw ResolutionException::methodNotFound(
                    $class->getName(),
                    $definition->methodName,
                );
            }
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

            if (array_key_exists($parameter->getName(), $definition->parameters)) {
                $arguments[] = $definition->parameters[$parameter->getName()];

                continue;
            }

            if (array_key_exists($parameter->getPosition(), $definition->parameters)) {
                $arguments[] = $definition->parameters[$parameter->getPosition()];

                continue;
            }

            $type = $parameter->getType();
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
                    'parent' => $class->getParentClass()?->getName() ?: null,
                    default  => $memberType->getName(),
                };

                if (null !== $candidate) {
                    $candidates[] = $candidate;
                }
            }

            $candidates = array_values(array_unique($candidates));
            $available = [];

            foreach ($candidates as $candidate) {
                if ($this->container->has($candidate)) {
                    $available[] = $candidate;
                }
            }

            $availableCount = count($available);

            if (1 === $availableCount) {
                $arguments[] = $this->container->get($available[0]);

                continue;
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
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            if (true === $type?->allowsNull()) {
                $arguments[] = null;

                continue;
            }

            throw ResolutionException::unresolvableParameter(
                $class->getName(),
                $method->getName(),
                $parameter->getName(),
                null === $type ? null : (string)$type,
            );
        }

        return $arguments;
    }
}
