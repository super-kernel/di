<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Fixture;

use SuperKernel\Attribute\Provider;

#[Provider(ProviderSubcontract::class)]
final class AnnotatedProvider implements ProviderSubcontract
{
}
