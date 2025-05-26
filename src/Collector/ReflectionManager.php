<?php

declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @ReflectionManager
 * @\SuperKernel\Di\Reflection\ReflectionManager
 */
final class ReflectionManager
{
	private static array $collectors = [];

	public function reflectClass(string $classname): ReflectionClass
	{
		if (!isset(self::$collectors['_c'][$classname])) {
			if (class_exists($classname) || interface_exists($classname) || trait_exists($classname)) {
				return self::$collectors['_c'][$classname] = new ReflectionClass($classname);
			}
			throw new InvalidArgumentException("Class $classname not exist");
		}
		return self::$collectors['_c'][$classname];
	}

	public function reflectInstanceWithoutConstruct(string $classname): object
	{
		if (!isset(self::$collectors['_instance'][$classname])) {
			$classReflection = $this->reflectClass($classname);
			if (!$classReflection->isInstantiable()) {
				throw new InvalidArgumentException("Class $classname is not instantiable");
			}
			if ($classReflection->isInternal() && $classReflection->isFinal()) {
				throw new InvalidArgumentException("Class $classname is internal and final");
			}
			return self::$collectors['_instance'][$classname] ??= $classReflection->newInstanceWithoutConstructor();
		}
		return self::$collectors['_instance'][$classname];
	}

	public function reflectMethod(string $classname, string $methodName): ReflectionMethod
	{
		$method = $classname . '::' . $methodName;
		if (!isset(self::$collectors['_m'][$method])) {
			if ((class_exists($classname) || interface_exists($classname)) && method_exists($classname, $methodName)) {
				return self::$collectors['_m'][$method] ??= $this->reflectClass($classname)->getMethod($methodName);
			}
			throw new InvalidArgumentException("Class $classname dont have method $methodName");
		}
		return self::$collectors['_m'][$method];
	}

	public function reflectProperty(string $classname, string $propertyName): ReflectionProperty
	{
		$property = $classname . '::' . $propertyName;
		if (!isset(self::$collectors['_p'][$property])) {
			if (class_exists($classname) && property_exists($classname, $propertyName)) {
				return self::$collectors['_p'][$property] ??= $this->reflectClass($classname)->getProperty(
					$propertyName,
				);
			}
			throw new InvalidArgumentException("Class $classname dont have property $propertyName");
		}
		return self::$collectors['_p'][$property];
	}
}