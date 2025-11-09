<?php
declare(strict_types=1);

namespace SuperKernel\Di\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Provider
{
	public function __construct(public string $class, public int $priority = 0)
	{
	}
}