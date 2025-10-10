<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;

#[Resolver]
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

		$classname           = $definition->getClassName();
		$reflectClass        = ReflectionManager::reflectClass($classname);
		$parameterDefinition = new ParameterDefinition($classname, '__construct');
		$arguments           = $this->resolverDispatcher->getResolver($parameterDefinition)->resolve($parameterDefinition, $parameters);

		return $reflectClass->newLazyProxy(function (object $object) use ($reflectClass, $arguments, $definition) {
			try {
				return new $object(...$arguments);
			}
			catch (ReflectionException $e) {
				throw InvalidDefinitionException::create(
					$definition,
					sprintf('Failed to lazy initialize class "%s": %s', $reflectClass->getName(), $e->getMessage()),
					$e,
				);
			}
		});
	}
}