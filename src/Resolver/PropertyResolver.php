<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;
use function is_null;

#[Resolver]
final class PropertyResolver implements ResolverInterface
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

	public function __construct(private readonly ContainerInterface $container)
	{
	}

	public function support(DefinitionInterface $definition): bool
	{
		return $definition instanceof PropertyDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	public function resolve(DefinitionInterface $definition): mixed
	{
		if (!($definition instanceof PropertyDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$className = $definition->getClassName();
		$propertyName = $definition->getName();
		$autowired = $this->getAutowired($className, $propertyName);


		if (!is_null($autowired)) {
			$class = $autowired->class;
			if (!is_null($class)) {
				return $this->container->get($class);
			}
		}

		return $this->getPropertyValue($className, $propertyName, $definition);
	}

	private function getAutowired(string $className, string $propertyName): ?Autowired
	{
		foreach ($this->annotationCollector->getPropertyAttributes($className, $propertyName) as $attribute) {
			if ($attribute->getAttribute() === Autowired::class) {
				$instance = $attribute->getInstance();
				if ($instance instanceof Autowired) {
					return $instance;
				}
			}
		}

		return null;
	}

	/**
	 * @param string             $className
	 * @param string             $propertyName
	 * @param PropertyDefinition $definition
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws ReflectionException
	 */
	private function getPropertyValue(string $className, string $propertyName, PropertyDefinition $definition): mixed
	{
		$reflectProperty = $this->reflectionCollector->reflectProperty($className, $propertyName);

		$typeName = $reflectProperty->getType()?->getName();
		if (null !== $typeName && $this->container->has($typeName)) {
			return $this->container->get($typeName);
		}

		if ($reflectProperty->hasDefaultValue()) {
			return $reflectProperty->getDefaultValue();
		}

		throw ResolverException::propertyNotResolvable($definition->getName(), $propertyName);
	}
}