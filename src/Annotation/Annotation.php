<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;
use Reflector;
use SuperKernel\Di\Contract\AnnotationInterface;

#[Attribute(Attribute::TARGET_CLASS)]
final class Annotation implements AnnotationInterface
{
	public function __construct(public string|array $id)
	{
	}

	/**
	 * @param Reflector $reflector
	 *
	 * @return void
	 */
	public function process(Reflector $reflector): void
	{
		// TODO: Implement process() method.
	}
}