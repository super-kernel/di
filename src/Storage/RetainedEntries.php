<?php

declare(strict_types=1);

namespace SuperKernel\Di\Storage;

use SuperKernel\Di\Contract\RetainedEntriesInterface;

use function array_key_exists;

/**
 * Retains root-container entries without allowing an identifier to be rebound.
 *
 * @internal
 */
final class RetainedEntries implements RetainedEntriesInterface
{
    /** @var array<string, mixed> */
    private array $entries = [];

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }

    public function get(string $id): mixed
    {
        return $this->entries[$id];
    }

    public function retain(string $id, mixed $value): mixed
    {
        if (array_key_exists($id, $this->entries)) {
            return $this->entries[$id];
        }

        return $this->entries[$id] = $value;
    }
}
