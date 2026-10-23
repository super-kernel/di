<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Provider;

#[Provider(ProviderSubcontract::class)]
final class LazyAnnotatedProvider implements ProviderSubcontract
{
    public static int $constructions = 0;

    private int $state;

    public function __construct()
    {
        ++self::$constructions;
        $this->state = 1;
    }

    public function state(): int
    {
        return $this->state;
    }
}
