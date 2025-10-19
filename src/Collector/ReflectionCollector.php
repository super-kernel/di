<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ReflectionCollectorInterface;

#[
	Provider(ReflectionCollector::class),
	Provider(ReflectionCollectorInterface::class),
]
final class ReflectionCollector implements ReflectionCollectorInterface
{
	private array $containers = [];

	public function reflectClass(string $class): ReflectionClass
	{
		if (!isset($this->containers['_c'][$class])) {
			if ((class_exists($class) || interface_exists($class))) {
				return $this->containers['_c'][$class] ??= new ReflectionClass($class);

			}
			throw new InvalidArgumentException("Class $class dont exist.");
		}

		return $this->containers['_c'][$class];
	}

	public function reflectMethod(string $classname, string $methodName): ReflectionMethod
	{
		$method = $classname . '::' . $methodName;
		if (!isset($this->containers['_m'][$method])) {
			if (class_exists($classname) || interface_exists($classname)) {
				$reflectClass = self::reflectClass($classname);
				if ($reflectClass->hasMethod($methodName)) {
					return $this->containers['_m'][$method] ??= $reflectClass->getMethod($methodName);
				}
			}
			throw new InvalidArgumentException("The class $classname does not have method $methodName.");
		}
		return $this->containers['_m'][$method];
	}

	public function reflectProperty(string $classname, string $propertyName): ReflectionProperty
	{
		$property = $classname . '::' . $propertyName;
		if (!isset($this->containers['_p'][$property])) {
			$reflectClass = self::reflectClass($classname);
			if ($reflectClass->hasProperty($propertyName)) {
				return $this->containers['_p'][$property] ??= $reflectClass->getProperty($propertyName);
			}
			throw new InvalidArgumentException("Class $classname dont have property $propertyName.");
		}
		return $this->containers['_p'][$property];
	}

	public function getAttributesByClass(string $classname, ?string $attributeName = null): array
	{
		$attribute = $classname . '::' . $attributeName;

		if (!isset($this->containers['_a'][$attribute])) {
			if (class_exists($classname) || interface_exists($classname)) {
				return $this->containers['_a'][$attribute] ??= self::reflectClass($classname)->getAttributes($attributeName);
			}

			throw new InvalidArgumentException("Class $classname dont have attribute $attributeName.");
		}

		return $this->containers['_a'][$attribute];
	}

	private function __clone(): void
	{
	}
}