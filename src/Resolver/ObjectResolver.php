<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\ObjectDefinition;
use SuperKernel\Di\Definition\MethodDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;
use Throwable;

#[Resolver]
final class ObjectResolver implements ResolverInterface
{
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

	private AnnotationCollectorInterface $annotationCollector {
		/**
		 * @throws ContainerExceptionInterface
		 * @throws NotFoundExceptionInterface
		 */
		get {
			if (!isset($this->annotationCollector)) {
				$this->annotationCollector = $this->container->get(AnnotationCollectorInterface::class);
			}
			return $this->annotationCollector;
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
	 */
	public function resolve(DefinitionInterface $definition, array $parameters = []): object
	{
		if (!($definition instanceof ObjectDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$className = $definition->getClassName();

		try {
			$reflectionClass = $this->reflectionCollector->reflectClass($className);


			return $reflectionClass->newLazyGhost(function (object $instance) use (
				$reflectionClass, $className, $parameters,
			) {
				$this->injectAutowiredProperties($instance, $reflectionClass);

				if ($reflectionClass->hasMethod('__construct')) {
					$methodDefinition = new MethodDefinition($className, '__construct', $parameters);

					$arguments = $this->resolverFactory->getResolver($methodDefinition)->resolve($methodDefinition);

					$instance->__construct(...$arguments);
				}
			});
		}
		catch (Throwable $throwable) {
			throw ResolverException::lazyInitializationNotSupported($definition->getName(), $throwable);
		}
	}

	/**
	 * @param object          $object
	 * @param ReflectionClass $reflection
	 *
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	private function injectAutowiredProperties(object $object, ReflectionClass $reflection): void
	{
		$className = $reflection->getName();
		$attributes = $this->annotationCollector->getPropertiesByAttribute(Autowired::class);

		foreach ($attributes as $attribute) {
			if ($attribute->getClass() !== $className) {
				continue;
			}

			$propertyName = $attribute->getProperty();
			$propertyReflection = $reflection->getProperty($propertyName);

			$value = $this->container->get($propertyReflection->getType()?->getName());
			$propertyReflection->setValue($object, $value);
		}
	}
}