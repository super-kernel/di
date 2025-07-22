<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

abstract class CollectorAbstract
{
	protected static array $collectors = [];

	public static function get(string $key, mixed $default = null): mixed
	{
		return self::$collectors[$key] ?? $default;
	}

	public static function set(string $key, mixed $value): void
	{
		self::$collectors[$key] = $value;
	}

	public static function has(string $key): bool
	{
		return array_key_exists($key, self::$collectors);
	}

	public static function clear(?string $key = null): void
	{
		if ($key) {
			unset(self::$collectors[$key]);
		} else {
			self::$collectors = [];
		}
	}

	public static function serialize(): string
	{
		return serialize(self::$collectors);
	}

	public static function deserialize(string $serialized): void
	{
		self::$collectors = unserialize($serialized) ?: [];
	}

	public static function list(): array
	{
		return self::$collectors;
	}
}