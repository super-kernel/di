<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

final class SkippingDefiner implements DefinerInterface
{
    public static int $supportCalls = 0;

    public function support(string $id): bool
    {
        ++self::$supportCalls;

        return 'other' === $id;
    }

    public function get(string $id): DefinitionInterface
    {
        return new NamedDefinition('other');
    }
}
