<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

/**
 * @MetadataManager
 * @\SuperKernel\Di\Abstract\MetadataManager
 */
abstract class MetadataManager
{
    static protected array $metadata = [];

    static public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$metadata)) {
            return static::$metadata[$key];
        }
        return $default;
    }

    static public function set(string $key, mixed $value): void
    {
        static::$metadata [$key] = $value;
    }

    static public function has(string $key): bool
    {
        return array_key_exists($key, static::$metadata);
    }

    static public function all(): array
    {
        return static::$metadata;
    }

    static public function clear(): void
    {
        static::$metadata = [];
    }
}