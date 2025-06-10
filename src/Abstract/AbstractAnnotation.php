<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

use Reflector;

abstract class AbstractAnnotation
{
	abstract public function __construct();

	abstract public function process(Reflector $reflector): mixed;
}