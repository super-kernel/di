<?php
declare(strict_types=1);

namespace SuperKernel\Di\Annotation;

use Attribute;
use ReflectionClass;
use Reflector;
use SuperKernel\Di\Contract\AnnotationInterface;

/**
 * @Factory
 * @\SuperKernel\Di\Annotation\Factory
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Factory implements AnnotationInterface
{
	public function handle(ReflectionClass $reflectionClass): void
	{
	}

	/**
	 * @param Reflector               $reflector
	 *
	 * @phpstan-param ReflectionClass $reflector
	 *
	 * @return mixed
	 */
	public function process(Reflector $reflector): mixed
	{
	}
}