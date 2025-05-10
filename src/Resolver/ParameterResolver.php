<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\CircularDependencyException;
use SuperKernel\Di\Exception\InvalidDefinitionException;
use SuperKernel\Di\Interface\DefinitionInterface;
use SuperKernel\Di\Interface\ResolverInterface;

/**
 * @ParameterResolver
 * @\SuperKernel\Di\Resolver\ParameterResolver
 */
final readonly class ParameterResolver implements ResolverInterface
{
	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(private ContainerInterface $container)
	{
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

		$this->checkDeepSecurity($definition->getClassname());

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

			if (is_string($typeName) && (class_exists($typeName) || interface_exists($typeName))) {
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

	private function checkDeepSecurity(string $class, array $params = []): false|array
	{
		foreach (ReflectionManager::reflectClass($class)->getConstructor()?->getParameters() ?? [] as $reflectionParameter) {
			$type = $reflectionParameter->getType();

			if ($type?->isBuiltin()) {
				continue;
			}

			$name = $type?->getName();

			if (!is_string($name) || !class_exists($name)) {
				continue;
			}
			if (in_array($name, $params, true)) {
				return false;
			}

			$params []    = $name;
			$paramsResult = $this->checkDeepSecurity($name, $params);

			if (false === $paramsResult) {
				throw new CircularDependencyException(
					sprintf('depth limit reached due to the following dependencies: %s -> %s', $name, $class),
				);
			}

			$params[] = $paramsResult;
		}

		return $params;
	}
}