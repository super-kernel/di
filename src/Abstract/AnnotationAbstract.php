<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Reflector;

abstract class AnnotationAbstract
{
	/**
	 * @param Reflector $reflector
	 *
	 * @return mixed
	 */
	abstract public function process($reflector): mixed;
}