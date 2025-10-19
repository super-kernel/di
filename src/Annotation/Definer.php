<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Definer
{
	public function __construct(public int $priority = 0)
	{
	}
}