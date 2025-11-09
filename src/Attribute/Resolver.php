<?php
declare(strict_types=1);

namespace SuperKernel\Di\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Resolver
{
	public function __construct(public int $priority = 0)
	{
	}
}