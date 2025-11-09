<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Attribute\Autowired;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Collector\ReflectionCollector;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\ParameterDefinition;
use SuperKernel\Di\Exception\Exception;
use SuperKernel\Di\Exception\InvalidDefinitionException;
use Throwable;

#[Resolver]
final class ObjectResolver implements ResolverInterface
{
	private ?ResolverFactoryInterface $resolverDispatcher = null {
		get => $this->resolverDispatcher ??= $this->container->get(ResolverFactoryInterface::class);
	}

	/**
	 * @var ReflectionCollectorInterface|null
	 * @psalm-var ReflectionCollector
	 */
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
		return $definition instanceof ObjectDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return object
	 * @throws InvalidDefinitionException
	 * @throws ReflectionException
	 */
	public function resolve(DefinitionInterface $definition): object
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

		$classname    = $definition->getName();
		$reflectClass = $this->reflectionCollector->reflectClass($classname);

		return $reflectClass->newLazyGhost(fn(object $object) => $this->createInstance($object, $reflectClass));
	}

	/**
	 * @param object          $object
	 * @param ReflectionClass $reflectClass
	 *
	 * @return void
	 * @throws Exception
	 */
	private function createInstance(object $object, ReflectionClass $reflectClass): void
	{
		try {
			foreach ($reflectClass->getProperties() as $property) {
				if ($property->isStatic()) {
					continue;
				}

				if (!array_any(
					array   : $property->getAttributes(),
					callback: fn(ReflectionAttribute $attribute) => $attribute->getName() === Autowired::class)) {
					continue;
				}

				/* @var ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type */
				$type     = $property->getType();
				$typeName = $type?->getName();

				if (!$type?->isBuiltin()) {
					/** @noinspection PhpExpressionResultUnusedInspection */
					$property->setAccessible(true);

					if ($this->container->has($typeName)) {
						$property->setValue($object, $this->container->get($typeName));
						continue;
					}

					if ($property->hasDefaultValue()) {
						$property->setValue($object, $property->getDefaultValue());
						continue;
					}
				}

				throw new Exception(
					sprintf(
						'Unable to autowired for attribute %s, type %s was not found and no default value was provided',
						$property->getName(),
						$typeName,
					),
				);
			}

			if ($reflectClass->hasMethod('__construct')) {
				$parameterDefinition = new ParameterDefinition($reflectClass->getName(), '__construct');
				$arguments           = $this->resolverDispatcher->getResolver($parameterDefinition)->resolve($parameterDefinition);

				$object->__construct(...$arguments);
			}
		}
		catch (Throwable $e) {
			throw new Exception(
				sprintf('Failed to lazy initialize class "%s": %s', $reflectClass->getName(), $e->getMessage()),
			);
		}
	}
}