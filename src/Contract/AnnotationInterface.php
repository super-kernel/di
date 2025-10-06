<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use Reflector;

interface AnnotationInterface
{
	/**
	 * @param Reflector $reflector
	 *
	 * @return void
	 */
	public function process(Reflector $reflector): void;
}