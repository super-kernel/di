<?php

declare(strict_types=1);

namespace SuperKernel\Di\Contract;

/**
 * Identifies a definition that may be handled by the built-in resolver.
 */
interface DefinitionInterface
{
    public function identifier(): string;
}
