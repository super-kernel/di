<?php
declare(strict_types=1);

namespace SuperKernel\Di\Abstract;

abstract class AbstractFactory
{
	protected static mixed $instance = null;

	public function __invoke(): static
	{
		return self::$instance ??= $this;
	}
}