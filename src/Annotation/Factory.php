<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;

/**
 * @Factory
 * @\SuperKernel\Di\Annotation\Factory
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Factory
{
}