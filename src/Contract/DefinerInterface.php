<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

/**
 * Produces definitions for supported container identifiers.
 */
interface DefinerInterface
{
    public function support(string $id): bool;

    public function get(string $id): DefinitionInterface;
}
