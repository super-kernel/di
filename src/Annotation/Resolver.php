<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;

/**
 * @Resolver
 * @\SuperKernel\Di\Annotation\Resolver
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Resolver
{
	public function __construct(public int $priority)
	{
	}
}