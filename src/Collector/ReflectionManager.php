<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SuperKernel\Di\Annotation\Factory;

#[Factory]
final class ReflectionManager
{
	private static array $classes = [];

	/**
	 * @param string|object $objectOrClass
	 *
	 * @return ReflectionClass
	 * @throws ReflectionException
	 */
	public static function reflectClass(string|object $objectOrClass): ReflectionClass
	{
		if (isset(self::$classes[$objectOrClass]) || array_key_exists($objectOrClass, self::$classes)) {
			return self::$classes[$objectOrClass];
		}

		return self::$classes[$objectOrClass] = new ReflectionClass($objectOrClass);
	}

	public static function reflectMethod(string $classname, string $methodName): ReflectionMethod
	{
		$method = $classname . '::' . $methodName;
		if (!isset($classes['_m'][$method])) {
			if ((class_exists($classname) || interface_exists($classname))) {
				$reflectClass = self::reflectClass($classname);
				if ($reflectClass->hasMethod($method) || method_exists($classname, $methodName)) {
					return self::$classes['_m'][$method] ??= $reflectClass->getMethod($methodName);
				}
			}
			throw new InvalidArgumentException("Class $classname dont have method $methodName");
		}
		return $classes['_m'][$method];
	}

	public static function reflectProperty(string $classname, string $propertyName): ReflectionProperty
	{
		$property = $classname . '::' . $propertyName;
		if (!isset($classes['_p'][$property])) {
			if (class_exists($classname) && property_exists($classname, $propertyName)) {
				return self::$classes['_p'][$property] ??= self::reflectClass($classname)->getProperty(
					$propertyName,
				);
			}
			throw new InvalidArgumentException("Class $classname dont have property $propertyName");
		}
		return $classes['_p'][$property];
	}
}