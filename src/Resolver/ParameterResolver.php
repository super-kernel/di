<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
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

		return $this->resolveMethodParameters($reflectionMethod, $definition->getParameters());
	}

	/**
	 * Allows developers to build targets with any custom parameters.
	 *
	 * @param ReflectionMethod $reflectionMethod
	 * @param array            $parameters
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws InvalidDefinitionException
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	private function resolveMethodParameters(ReflectionMethod $reflectionMethod, array $parameters): array
	{
		$arguments = [];

		foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
			$arguments[] = $this->getParameter($reflectionParameter, $parameters);
		}

		return $arguments;
	}

	/**
	 * @param ReflectionParameter $reflectionParameter
	 * @param array               $parameters
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws InvalidDefinitionException
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function getParameter(ReflectionParameter $reflectionParameter, array $parameters): mixed
	{
		$name = $reflectionParameter->getName();

		// Return the value if the parameter name exists in the `$parameters` array.
		if (isset($parameters[$name])) {
			return $parameters[$name];
		}

		// Return the value if the parameter name exists in the `$this->container`.
		if ($reflectionParameter->hasType()) {
			$id = $reflectionParameter->getType()->getName();

			if ($this->container->has($id)) {
				return $this->container->get($id);
			}
		}

		// Return the value if the parameter name exists default value.
		if ($reflectionParameter->isOptional()) {
			if ($reflectionParameter->isDefaultValueConstant()) {
				/** @noinspection PhpUnhandledExceptionInspection */
				$constName = $reflectionParameter->getDefaultValueConstantName();

				return constant($constName);
			}
			if ($reflectionParameter->isDefaultValueAvailable()) {
				return $reflectionParameter->getDefaultValue();
			}
		}

		throw new InvalidDefinitionException("The $$name parameter did not provide a default value");
	}
}