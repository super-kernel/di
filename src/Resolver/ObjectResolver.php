<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use SuperKernel\Annotation\Autowired;
use SuperKernel\Attribute\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectorInterface;
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

	private AttributeCollectorInterface $attributeCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->attributeCollector)) {
				$this->attributeCollector = $this->container->get(AttributeCollectorInterface::class);
			}
			return $this->attributeCollector;
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
	 */
	public function resolve(DefinitionInterface $definition): object
	{
		if (!($definition instanceof ObjectDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$reflectClass = $this->reflector->reflectClass($definition->getClassName());
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
			foreach ($this->attributeCollector->getPropertiesByAttribute(Autowired::class) as $attribute) {
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