<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Contract\DefinitionInterface;

final class LowPriorityDefiner implements DefinerInterface
{
    public static int $supportCalls = 0;

    public static int $getCalls = 0;

    public function support(string $id): bool
    {
        ++self::$supportCalls;

        return 'service' === $id;
    }

    public function get(string $id): DefinitionInterface
    {
        ++self::$getCalls;

        return new NamedDefinition('low');
    }
}
