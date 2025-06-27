<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;

/**
 * @ObjectResolver
 * @\SuperKernel\Di\Resolver\ObjectResolver
 */
final class ObjectResolver implements ResolverInterface
{
	private ?ResolverFactoryInterface $resolverDispatcher = null {
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
		return $definition instanceof ObjectDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 * @param array               $parameters
	 *
	 * @return object
	 * @throws InvalidDefinitionException
	 * @throws ContainerExceptionInterface
	 */
	public function resolve(DefinitionInterface $definition, array $parameters = []): object
	{
		if (!($definition instanceof ObjectDefinition)) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf(
					'Entry "%s" cannot be resolved: the class is not instanceof ObjectDefinition',
					$definition->getName(),
				),
			);
		}
		if (!$definition->isInstantiable()) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf('Entry "%s" cannot be resolved: the class is not instantiable', $definition->getName()),
			);
		}
		if (!$definition->isClassExists()) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf('Entry "%s" cannot be resolved: the class does not exist', $definition->getName()),
			);
		}

		$classname = $definition->getClassName();

		try {
			$reflectClass = new ReflectionManager()->reflectClass($classname);
		}
		catch (ReflectionException $reflectionException) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf('Entry "%s" cannot be resolved: %s', $definition->getName(), $reflectionException->getMessage()),
			);
		}

		$parameterDefinition = new ParameterDefinition($classname, '__construct');
		$arguments           = $this->resolverDispatcher->getResolver($parameterDefinition)->resolve($parameterDefinition, $parameters);

//		return $reflectClass->newLazyGhost(function (object $object) use ($arguments) {
//			if (method_exists($object, '__construct')) {
//				$object->__construct(...$arguments);
//			}
//		});

		try {
			return $reflectClass->newInstance(...$arguments);
		}
		catch (ReflectionException $reflectionException) {
			throw InvalidDefinitionException::create(
				$definition,
				sprintf('Entry "%s" cannot be create instance: %s', $definition->getName(), $reflectionException->getMessage()),
			);
		}
	}
}