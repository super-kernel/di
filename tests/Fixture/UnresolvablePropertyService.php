<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Autowired;

final class UnresolvablePropertyService
{
    #[Autowired]
    public int $port;
}
