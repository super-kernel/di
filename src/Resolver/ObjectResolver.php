<?php

declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use SuperKernel\Di\Collector\ReflectionManager;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\InvalidDefinitionException;
use SuperKernel\Di\Interface\DefinitionInterface;
use SuperKernel\Di\Interface\ResolverInterface;

/**
 * @ObjectResolver
 * @\SuperKernel\Di\Resolver\ObjectResolver
 */
final readonly class ObjectResolver implements ResolverInterface
{
	public function __construct(private ContainerInterface $container, private ResolverInterface $resolverDispatcher)
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

		$classname       = $definition->getClassName();
		$classReflection = ReflectionManager::reflectClass($classname);

		$arguments = $this->resolverDispatcher->resolve(new ParameterDefinition($classname, '__construct'), $parameters);

		return $classReflection->newLazyGhost(function (object $object) use ($arguments) {
			if (method_exists($object, '__construct')) {
				$object->__construct(...$arguments);
			}
		});
	}
}