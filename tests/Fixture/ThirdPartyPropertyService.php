<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

final class ThirdPartyPropertyService
{
    #[ThirdPartyPropertyAttribute('handled')]
    public string $handled;

    #[ThirdPartyPropertyAttribute(null)]
    public ?string $nullable = 'fallback';

    #[ThirdPartyPropertyAttribute('ignored', assign: false)]
    public string $untouched = 'default';
}
