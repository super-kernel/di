<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use SuperKernel\Annotation\Autowired;
use SuperKernel\Contract\AttributeMetadataCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;
use Throwable;
use function method_exists;

#[Resolver]
final class ObjectResolver implements ResolverInterface
{
	private ResolverFactoryInterface $resolverFactory {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->resolverFactory)) {
				$this->resolverFactory = $this->container->get(ResolverFactoryInterface::class);
			}
			return $this->resolverFactory;
		}
	}

	private ReflectionCollectorInterface $reflectionCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->reflectionCollector)) {
				$this->reflectionCollector = $this->container->get(ReflectionCollectorInterface::class);
			}
			return $this->reflectionCollector;
		}
	}

	private AttributeMetadataCollectorInterface $attributeMetadataCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->attributeMetadataCollector)) {
				$this->attributeMetadataCollector = $this->container->get(AttributeMetadataCollectorInterface::class);
			}
			return $this->attributeMetadataCollector;
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
		return $definition instanceof ObjectDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return object
	 * @throws ReflectionException
	 */
	public function resolve(DefinitionInterface $definition): object
	{
		if (!($definition instanceof ObjectDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$reflectClass = $this->reflectionCollector->reflectClass($definition->getClassName());
		return $reflectClass->newLazyGhost(
			initializer: fn(object $object) => $this->createInstance($object, $reflectClass),
		);
	}

	/**
	 * @param object          $object
	 * @param ReflectionClass $reflectClass
	 *
	 * @return void
	 */
	private function createInstance(object $object, ReflectionClass $reflectClass): void
	{
		$className = $reflectClass->getName();
		$properties = [];

		try {
			foreach ($this->attributeMetadataCollector->getPropertiesByAttribute(Autowired::class) as $attribute) {
				if ($attribute->getClass() === $className) {
					$propertyName = $attribute->getProperty();
					$propertyDefinition = new PropertyDefinition($propertyName, $className);
					$propertyValue = $this->resolverFactory->getResolver($propertyDefinition)->resolve($propertyDefinition);
					$properties[$propertyName] = $propertyValue;
				}
			}

			foreach ($properties as $name => $value) {
				$reflectionProperty = $reflectClass->getProperty($name);
				if (method_exists($reflectionProperty, 'setAccessible')) {
					/** @noinspection PhpExpressionResultUnusedInspection */
					$reflectionProperty->setAccessible(true);
				}
				$reflectionProperty->setRawValueWithoutLazyInitialization($object, $value);
			}

			if ($reflectClass->hasMethod('__construct')) {
				$parameterDefinition = new MethodDefinition($className, '__construct');

				$arguments = $this->resolverFactory->getResolver($parameterDefinition)->resolve($parameterDefinition);
				$object->__construct(...$arguments);
			}
		}
		catch (Throwable $throwable) {
			throw ResolverException::lazyInitializationNotSupported($className, $throwable);
		}
	}
}