<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

/**
 * Defines write-once storage for root-container entries.
 *
 * @internal
 */
interface RetainedEntriesInterface
{
    public function has(string $id): bool;

    public function get(string $id): mixed;

    public function retain(string $id, mixed $value): mixed;
}
