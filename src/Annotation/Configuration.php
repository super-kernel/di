<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;
use Reflector;
use SuperKernel\Di\Contract\AnnotationInterface;

#[Attribute(Attribute::TARGET_CLASS)]
final class Configuration implements AnnotationInterface
{
	public function __construct(public string $class)
	{
	}

	/**
	 * @param Reflector $reflector
	 *
	 * @return mixed
	 */
	public function process(Reflector $reflector): mixed
	{
		// TODO: Implement process() method.
	}
}