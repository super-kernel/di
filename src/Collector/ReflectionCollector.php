<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SuperKernel\Attribute\Provider;
use SuperKernel\Di\Contract\ReflectionCollectorInterface;
use function get_class;
use function is_object;

#[
	Provider(ReflectionCollector::class),
	Provider(ReflectionCollectorInterface::class),
]
final class ReflectionCollector implements ReflectionCollectorInterface
{
	private array $classes = [];

	private array $methods = [];

	private array $properties = [];

	/**
	 * @param object|string $objectOrClass
	 *
	 * @return ReflectionClass
	 * @throws ReflectionException
	 */
	public function reflectClass(object|string $objectOrClass): ReflectionClass
	{
		$class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;
		return $this->classes[$class] ??= new ReflectionClass($objectOrClass);
	}

	/**
	 * @param object|string $objectOrMethod
	 * @param string        $method
	 *
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	public function reflectMethod(object|string $objectOrMethod, string $method): ReflectionMethod
	{
		$class = is_object($objectOrMethod) ? get_class($objectOrMethod) : $objectOrMethod;
		$key   = $class . '::' . $method;
		return $this->methods[$key] ??= $this->reflectClass($objectOrMethod)->getMethod($method);
	}

	/**
	 * @param object|string $objectOrMethod
	 * @param string        $name
	 *
	 * @return ReflectionProperty
	 * @throws ReflectionException
	 */
	public function reflectProperty(object|string $objectOrMethod, string $name): ReflectionProperty
	{
		$class = is_object($objectOrMethod) ? get_class($objectOrMethod) : $objectOrMethod;
		$key   = $class . '::' . $name;
		return $this->properties[$key] ??= $this->reflectClass($objectOrMethod)->getProperty($name);
	}

	private function __clone(): void
	{
	}
}