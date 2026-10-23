<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class StaticInjectedService
{
    #[Autowired]
    public static string $value = 'default';
}
