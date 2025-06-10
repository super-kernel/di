<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SuperKernel\Di\Abstract\AbstractFactory;
use SuperKernel\Di\Annotation\Factory;

#[Factory]
final class ReflectionManager extends AbstractFactory
{
	private array $classes = [];

	public function reflectClass(string|object $objectOrClass): ?ReflectionClass
	{
		if (isset($this->classes[$objectOrClass]) || array_key_exists($objectOrClass, $this->classes)) {
			return $this->classes[$objectOrClass];
		}

		try {
			return $this->classes[$objectOrClass] = new ReflectionClass($objectOrClass);
		}
		catch (ReflectionException) {
			return $this->classes[$objectOrClass] = null;
		}
	}

	public function reflectInstanceWithoutConstruct(string $classname): object
	{
		if (!isset($this->collectors['_instance'][$classname])) {
			$classReflection = $this->reflectClass($classname);
			if (!$classReflection->isInstantiable()) {
				throw new InvalidArgumentException("Class $classname is not instantiable");
			}
			if ($classReflection->isInternal() && $classReflection->isFinal()) {
				throw new InvalidArgumentException("Class $classname is internal and final");
			}
			return $this->collectors['_instance'][$classname] ??= $classReflection->newInstanceWithoutConstructor();
		}
		return $this->collectors['_instance'][$classname];
	}

	public function reflectMethod(string $classname, string $methodName): ReflectionMethod
	{
		$method = $classname . '::' . $methodName;
		if (!isset($this->collectors['_m'][$method])) {
			if ((class_exists($classname) || interface_exists($classname)) && method_exists($classname, $methodName)) {
				return $this->collectors['_m'][$method] ??= $this->reflectClass($classname)->getMethod($methodName);
			}
			throw new InvalidArgumentException("Class $classname dont have method $methodName");
		}
		return $this->collectors['_m'][$method];
	}

	public function reflectProperty(string $classname, string $propertyName): ReflectionProperty
	{
		$property = $classname . '::' . $propertyName;
		if (!isset($this->collectors['_p'][$property])) {
			if (class_exists($classname) && property_exists($classname, $propertyName)) {
				return $this->collectors['_p'][$property] ??= $this->reflectClass($classname)->getProperty(
					$propertyName,
				);
			}
			throw new InvalidArgumentException("Class $classname dont have property $propertyName");
		}
		return $this->collectors['_p'][$property];
	}
}