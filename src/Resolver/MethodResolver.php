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
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;
use Throwable;
use function constant;

#[Resolver]
final class MethodResolver implements ResolverInterface
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

	public function support(DefinitionInterface $definition): bool
	{
		return $definition instanceof MethodDefinition;
	}

	public function resolve(DefinitionInterface $definition): array
	{
		if (!($definition instanceof MethodDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$class = $definition->getClassName();
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
	 * @param MethodDefinition    $definition
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function getParameter(ReflectionParameter $reflectionParameter, MethodDefinition $definition): mixed
	{
		$parameterName = $reflectionParameter->getName();
		$parameters = $definition->getParameters();

		if (isset($parameters[$parameterName])) {
			return $parameters[$parameterName];
		}

		$position = $reflectionParameter->getPosition();
		if (isset($parameters[$position])) {
			return $parameters[$position];
		}

		if ($reflectionParameter->hasType()) {
			$id = $reflectionParameter->getType()->getName();
			if ($this->container->has($id)) {
				return $this->container->get($id);
			}
		}

		if ($reflectionParameter->isOptional()) {
			if ($reflectionParameter->isDefaultValueConstant()) {
				return constant($reflectionParameter->getDefaultValueConstantName());
			}
			if ($reflectionParameter->isDefaultValueAvailable()) {
				return $reflectionParameter->getDefaultValue();
			}
		}

		throw ResolverException::parameterNotResolvable($definition, $parameterName);
	}
}