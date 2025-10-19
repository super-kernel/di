<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Autowired
{
	public function __construct()
	{
	}
}