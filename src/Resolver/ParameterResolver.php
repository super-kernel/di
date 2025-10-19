<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;

#[Resolver(3)]
final class ParameterResolver implements ResolverInterface
{
	private ?ResolverInterface $resolverDispatcher = null {
		get => $this->resolverDispatcher ??= $this->container->get(ResolverFactoryInterface::class);
	}

	private ?ReflectionCollectorInterface $reflectionCollector = null {
		get => $this->reflectionCollector ??= $this->container->get(ReflectionCollectorInterface::class);
	}

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return bool
	 */
	public function support(DefinitionInterface $definition): bool
	{
		return $definition instanceof ParameterDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws InvalidDefinitionException
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function resolve(DefinitionInterface $definition): array
	{
		if (!$definition instanceof ParameterDefinition) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf('Entry "%s" cannot be resolved: the class is not instanceof ParameterDefinition', $definition->getName()),
			);
		}

		$reflectionMethod = $this->reflectionCollector->reflectMethod($definition->getName(), $definition->getMethodName());

		return $this->resolveMethodParameters($reflectionMethod);
	}

	/**
	 * @param ReflectionMethod $reflectionMethod
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws InvalidDefinitionException
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function resolveMethodParameters(ReflectionMethod $reflectionMethod): array
	{
		$arguments = [];

		foreach ($reflectionMethod->getParameters() as $parameter) {
			$parameterName = $parameter->getName();

			$typeName = $parameter->getType()?->getName();


			if (is_string($typeName) && $this->container->has($typeName)) {
				$arguments[] = $this->container->get($typeName);
				continue;
			}

			if ($parameter->isOptional()) {
				if ($parameter->isDefaultValueConstant()) {
					/** @noinspection PhpUnhandledExceptionInspection */
					$constName = $parameter->getDefaultValueConstantName();

					$arguments[] = constant($constName);
					continue;
				}
				if ($parameter->isDefaultValueAvailable()) {
					$arguments[] = $parameter->getDefaultValue();
					continue;
				}
			}

			throw new InvalidDefinitionException(
				sprintf(
					'The $%s parameter of method %s did not provide a default value',
					$parameterName,
					$reflectionMethod->getName(),
				),
			);
		}

		return $arguments;
	}
}