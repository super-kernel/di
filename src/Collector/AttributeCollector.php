<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use Composer\Autoload\ClassLoader;
use RuntimeException;
use SuperKernel\Di\Attribute\Provider;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
use SuperKernel\Di\Contract\ReflectionCollectorInterface;
use Throwable;

#[
	Provider(AttributeCollector::class),
	Provider(AttributeCollectorInterface::class),
]
final class AttributeCollector implements AttributeCollectorInterface
{
	private array $attributes;

	private ?ClassLoader $classLoader = null {
		get {
			if (null === $this->classLoader) {
				foreach (spl_autoload_functions() as [$loader]) {
					if ($loader instanceof ClassLoader) {
						$this->classLoader = $loader;
						break;
					}
				}
				if (null === $this->classLoader) {
					throw new RuntimeException('Composer loader not found.');
				}
			}
			return $this->classLoader;
		}
	}

	/**
	 * @param ReflectionCollectorInterface $reflectionCollector
	 *
	 * @psalm-param ReflectionCollector    $reflectionCollector
	 */
	public function __construct(ReflectionCollectorInterface $reflectionCollector)
	{
		foreach ($this->classLoader->getClassMap() as $class => $path) {
			try {
				$reflectionClass = $reflectionCollector->reflectClass($class);
			}
				// Given that some component designs may have non-standard issues, it is necessary to use `\Throwable` to skip the loop here.
			catch (Throwable) {
				continue;
			}
			foreach ($reflectionClass->getAttributes() as $attribute) {
				$this->attributes[$attribute->getName()][] = new Attribute($class, $attribute->newInstance());
			}
		}
	}

	public function getAttributes(string $name, int $flags = 0): array
	{
		if ($flags !== AttributeCollectorInterface::IS_INSTANCEOF) {
			return isset($this->attributes[$name]) ? [$this->attributes[$name]] : [];
		}

		$attributes = [];
		foreach ($this->attributes as $attribute) {
			if ($attribute->attribute instanceof $name) {
				$attributes[] = $attribute;
			}
		}
		return $attributes;
	}

	private function __clone(): void
	{
	}
}