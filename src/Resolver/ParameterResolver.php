<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\CircularDependencyException;
use SuperKernel\Di\Exception\InvalidDefinitionException;

#[Resolver(3)]
final class ParameterResolver implements ResolverInterface
{
	private ?ResolverInterface $resolverDispatcher = null {
		get => $this->resolverDispatcher ??= $this->container->get(ResolverFactoryInterface::class);
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
	 * @param array               $parameters
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws InvalidDefinitionException
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function resolve(DefinitionInterface $definition, array $parameters = []): array
	{
		if (!$definition instanceof ParameterDefinition) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf('Entry "%s" cannot be resolved: the class is not instanceof ParameterDefinition', $definition->getName()),
			);
		}

		return $this->resolveMethodParameters($definition->getReflectionMethod(), $parameters);
	}

	/**
	 * @param ReflectionMethod|null $reflectionMethod
	 * @param array                 $parameters
	 *
	 * @return array
	 * @throws InvalidDefinitionException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function resolveMethodParameters(?ReflectionMethod $reflectionMethod = null, array $parameters = []): array
	{
		if (null === $reflectionMethod) {
			return [];
		}

		$arguments = [];

		foreach ($reflectionMethod->getParameters() as $index => $parameter) {
			$parameterName = $parameter->getName();
			if (!empty($parameters)) {
				if (isset($parameters[$parameterName]) || array_key_exists($parameterName, $parameters)) {
					$arguments[] = &$parameters[$parameterName];
					continue;
				} elseif (isset($parameters[$index]) || array_key_exists($index, $parameters)) {
					$arguments[] = &$parameters[$index];
					continue;
				}
			}

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