<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;

/**
 * @Inject
 * @\SuperKernel\Di\Annotation\Inject
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Inject
{
	public function __construct(public ?string $value = null)
	{
	}
}