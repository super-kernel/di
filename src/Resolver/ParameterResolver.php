<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionParameter;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;
use Throwable;
use function constant;

#[Resolver]
final class ParameterResolver implements ResolverInterface
{
	private ReflectorInterface $reflector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
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
	 */
	public function resolve(DefinitionInterface $definition): array
	{
		if (!($definition instanceof ParameterDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$class = $definition->getName();
		$method = $definition->getMethodName();

		try {
			$reflectionMethod = $this->reflector->reflectMethod($class, $method);

			$arguments = [];
			foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
				$arguments[] = $this->getParameter($reflectionParameter, $definition);
			}

			return $arguments;
		}
		catch (Throwable $throwable) {
			throw ResolverException::parameterResolutionFailed($class, $method, $throwable);
		}
	}

	/**
	 * @param ReflectionParameter $reflectionParameter
	 * @param ParameterDefinition $definition
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function getParameter(ReflectionParameter $reflectionParameter, ParameterDefinition $definition): mixed
	{
		$parameterName = $reflectionParameter->getName();
		$parameters = $definition->getParameters();

		// Return the value if the parameter name exists in the `$parameters` array.
		if (isset($parameters[$parameterName])) {
			return $parameters[$parameterName];
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

		throw ResolverException::parameterNotResolvable($definition, $parameterName);
	}
}