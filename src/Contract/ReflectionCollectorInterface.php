<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Describes the interface for a reflector, which exposes methods for reading information about the reflected classes
 * its collects.
 */
interface ReflectionCollectorInterface
{
	/**
	 * Finds a reflector by class name and returns it.
	 *
	 * @param object|string $objectOrClass
	 *
	 * @return ReflectionClass
	 */
	public function reflectClass(object|string $objectOrClass): ReflectionClass;

	/**
	 * Finds a class reflector by class and method name and returns it.
	 *
	 * @param object|string $objectOrMethod
	 * @param string        $method
	 *
	 * @return ReflectionMethod
	 */
	public function reflectMethod(object|string $objectOrMethod, string $method): ReflectionMethod;

	/**
	 * Finds a method reflector by class and property name and returns it.
	 *
	 * @param object|string $objectOrMethod
	 * @param string        $name
	 *
	 * @return ReflectionProperty
	 */
	public function reflectProperty(object|string $objectOrMethod, string $name): ReflectionProperty;
}