<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class ThirdPartyPropertyAttribute
{
    public function __construct(public ?string $value, public bool    $assign = true)
    {
    }
}
