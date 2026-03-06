<?php
declare(strict_types=1);

namespace SuperKernel\Di\Resolver;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionProperty;
use SuperKernel\Annotation\Autowired;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Di\Attribute\Resolver;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;
use SuperKernel\Di\Definition\PropertyDefinition;
use SuperKernel\Di\Exception\Container\ResolverException;
use function is_string;

#[Resolver]
final class PropertyResolver implements ResolverInterface
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
		return $definition instanceof PropertyDefinition;
	}

	/**
	 * @param DefinitionInterface $definition
	 *
	 * @return array
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function resolve(DefinitionInterface $definition): array
	{
		if (!($definition instanceof PropertyDefinition)) {
			throw ResolverException::unsupportedDefinition($definition);
		}

		$properties = [];
		foreach ($definition->getProperties($this->reflector) as $property) {
			$propertyName = $property->getName();

			$attribute = $property->getAttributes(Autowired::class);
			if (empty($attribute)) {
				continue;
			}
			if (!$property->hasType() || $property->isStatic()) {
				throw ResolverException::propertyNotResolvable($definition->getName(), $propertyName);
			}

			$properties[$propertyName] = $this->getPropertyValue($propertyName, $attribute[0]->newInstance(), $definition, $property);
		}

		return $properties;
	}

	/**
	 * @param string             $propertyName
	 * @param Autowired          $autowired
	 * @param PropertyDefinition $definition
	 * @param ReflectionProperty $property
	 *
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	private function getPropertyValue(
		string             $propertyName,
		Autowired          $autowired,
		PropertyDefinition $definition,
		ReflectionProperty $property,
	): mixed
	{
		$value = $autowired->class;
		if (null !== $value) {
			if (is_string($value) && $this->container->has($value)) {
				return $this->container->get($value);
			}
			return $value;
		}

		$type = $property->getType()?->getName();
		if (null !== $type && $this->container->has($type)) {
			return $this->container->get($type);
		}

		if ($property->hasDefaultValue()) {
			return $property->getDefaultValue();
		}

		throw ResolverException::propertyNotResolvable($definition->getName(), $propertyName);
	}
}