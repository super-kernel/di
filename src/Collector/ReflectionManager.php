<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use SuperKernel\Attribute\Contract;
use SuperKernel\Contract\ReflectionManagerInterface;

/**
 * @mixin ReflectionManagerInterface
 *
 * @method static ReflectionClass reflectClass(string $class)
 * @method static ReflectionMethod reflectMethod(string $classname, string $methodName)
 * @method static ReflectionProperty reflectProperty(string $classname, string $propertyName)
 * @method static array<string> getAttributes(string $name)
 * @method static array<ReflectionAttribute> getClassAnnotations(string $classname, ?string $attributeName = null)
 */
#[
	Contract(ReflectionManagerInterface::class),
]
final class ReflectionManager
{
	private static ?ReflectionManagerInterface $reflectionManager = null;

	public function __invoke(array $attributes): ReflectionManagerInterface
	{
		return self::$reflectionManager ??= new class($attributes) implements ReflectionManagerInterface {
			private static array $containers = [];

			public function __construct(private readonly array $attributes)
			{
			}

			public function reflectClass(string $class): ReflectionClass
			{
				if (!isset(self::$containers['_c'][$class])) {
					if ((class_exists($class) || interface_exists($class))) {
						return self::$containers['_c'][$class] ??= new ReflectionClass($class);

					}
					throw new InvalidArgumentException("Class $class dont exist.");
				}

				return self::$containers['_c'][$class];
			}

			public function reflectMethod(string $classname, string $methodName): ReflectionMethod
			{
				$method = $classname . '::' . $methodName;
				if (!isset(self::$containers['_m'][$method])) {
					if (class_exists($classname) || interface_exists($classname)) {
						$reflectClass = self::reflectClass($classname);
						if ($reflectClass->hasMethod($methodName)) {
							return self::$containers['_m'][$method] ??= $reflectClass->getMethod($methodName);
						}
					}
					throw new InvalidArgumentException("The class $classname does not have method $methodName.");
				}
				return self::$containers['_m'][$method];
			}

			public function reflectProperty(string $classname, string $propertyName): ReflectionProperty
			{
				$property = $classname . '::' . $propertyName;
				if (!isset(self::$containers['_p'][$property])) {
					$reflectClass = self::reflectClass($classname);
					if ($reflectClass->hasProperty($propertyName)) {
						return self::$containers['_p'][$property] ??= $reflectClass->getProperty($propertyName);
					}
					throw new InvalidArgumentException("Class $classname dont have property $propertyName.");
				}
				return self::$containers['_p'][$property];
			}

			public function getAttributes(string $name): array
			{
				return $this->attributes[$name] ?? [];
			}

			public function getClassAnnotations(string $classname, ?string $attributeName = null): array
			{
				$attribute = $classname . '::' . $attributeName;

				if (!isset(self::$containers['_a'][$attribute])) {
					if (class_exists($classname) || interface_exists($classname)) {
						return self::$containers['_a'][$attribute] ??= self::reflectClass($classname)->getAttributes($attributeName);
					}

					throw new InvalidArgumentException("Class $classname dont have attribute $attributeName.");
				}

				return self::$containers['_a'][$attribute];
			}
		};
	}

	public static function __callStatic(string $name, array $arguments): mixed
	{
		if (null === self::$reflectionManager) {
			throw new RuntimeException('ReflectionManager not initialized.');
		}

		if (method_exists(self::$reflectionManager, $name)) {
			return self::$reflectionManager->$name(...$arguments);
		}

		throw new RuntimeException("Method $name does not exist.");
	}

	private function __clone(): void
	{
	}
}