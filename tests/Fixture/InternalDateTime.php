<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use DateTimeImmutable;

final class InternalDateTime extends DateTimeImmutable
{
    public string $marker = 'inherited';
}
