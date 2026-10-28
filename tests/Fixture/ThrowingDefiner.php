<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use RuntimeException;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

final class ThrowingDefiner implements DefinerInterface
{
    public function support(string $id): bool
    {
        throw new RuntimeException('definition lookup failure');
    }

    public function get(string $id): DefinitionInterface
    {
        throw new RuntimeException('definition creation failure');
    }
}
