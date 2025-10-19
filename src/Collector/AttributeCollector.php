<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use Psr\Container\ContainerInterface;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\AttributeCollectorInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Exception\NotFoundException;

#[
	Provider(AttributeCollector::class),
	Provider(AttributeCollectorInterface::class),
]
final class AttributeCollector implements AttributeCollectorInterface
{
	private array $attributes;

	private array $containers = [];

	private array $realEntries = [];

	private ?ReflectionCollectorInterface $reflectionCollector = null {
		get => $this->reflectionCollector ??= $this->container->get(ReflectionCollectorInterface::class);
	}

	public function __construct(private readonly ContainerInterface $container, array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * @param string $attributeName
	 *
	 * @return array<string, array<object>>
	 */
	public function getAttributes(string $attributeName): array
	{
		if (!isset($this->containers[$attributeName])) {
			$instances = [];
			$classes   = $this->attributes[$attributeName] ?? [];

			foreach ($classes as $class) {
				$attributes = $this->reflectionCollector->getAttributesByClass($class, $attributeName);

				foreach ($attributes as $attribute) {
					if ($attribute->getName() === $attributeName) {
						$instances[$class][] = $attribute->newInstance();
					}
				}
			}

			$this->containers[$attributeName] = $instances;
		}

		return $this->containers[$attributeName];
	}

	/**
	 * {@inheritDoc}
	 * @throws NotFoundException
	 */
	public function getRealEntry(string $id): string
	{
		if (isset($this->realEntries[$id]) || array_key_exists($id, $this->realEntries)) {
			return $this->realEntries[$id];
		}

		$class    = $id;
		$priority = 0;

		/* @var array<Provider> $attributes */
		foreach ($this->getAttributes(Provider::class) as $classname => $attributes) {
			foreach ($attributes as $attribute) {
				if ($attribute->class === $id && $attribute->priority >= $priority) {
					$class    = $classname;
					$priority = $attribute->priority;
				}
			}
		}

		if (interface_exists($class)) {
			throw new NotFoundException("No provider found for $id");
		}

		return $this->realEntries[$id] = $class;
	}

	private function __clone(): void
	{
	}
}