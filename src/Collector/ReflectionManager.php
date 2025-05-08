<?php

declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use SuperKernel\Di\Abstract\AbstractCollector;

/**
 * @ReflectionManager
 * @\SuperKernel\Di\Reflection\ReflectionManager
 */
final class ReflectionManager extends AbstractCollector
{
	protected static array $collectors = [];

	public static function reflectClass(string $classname): ReflectionClass
	{
		if (!isset(self::$collectors['_c'][$classname])) {
			if (class_exists($classname) || interface_exists($classname) || trait_exists($classname)) {
				return self::$collectors['_c'][$classname] = new ReflectionClass($classname);
			}
			throw new InvalidArgumentException("Class $classname not exist");
		}
		return self::$collectors['_c'][$classname];
	}

	public static function reflectInstanceWithoutConstruct(string $classname): object
	{
		if (!isset(self::$collectors['_instance'][$classname])) {
			$classReflection = self::reflectClass($classname);
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

	public static function reflectMethod(string $classname, string $methodName): ReflectionMethod
	{
		$method = $classname . '::' . $methodName;
		if (!isset(self::$collectors['_m'][$method])) {
			if ((class_exists($classname) || interface_exists($classname)) && method_exists($classname, $methodName)) {
				return self::$collectors['_m'][$method] ??= self::reflectClass($classname)->getMethod($methodName);
			}
			throw new InvalidArgumentException("Class $classname dont have method $methodName");
		}
		return self::$collectors['_m'][$method];
	}

	public static function reflectProperty(string $classname, string $propertyName): \ReflectionProperty
	{
		$property = $classname . '::' . $propertyName;
		if (!isset(self::$collectors['_p'][$property])) {
			if (class_exists($classname) && property_exists($classname, $propertyName)) {
				return self::$collectors['_p'][$property] ??= self::reflectClass($classname)->getProperty(
					$propertyName,
				);
			}
			throw new InvalidArgumentException("Class $classname dont have property $propertyName");
		}
		return self::$collectors['_p'][$property];
	}
}