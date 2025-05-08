<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;

/**
 * @TestCase
 * @\SuperKernel\Di\Annotation\TestCase
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class TestCase
{
}