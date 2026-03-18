<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Collector\ReflectionCollector;

#[
	Provider(ReflectionCollectorInterface::class),
	Factory,
]
final class ReflectionCollectorProvider
{
	private static ReflectionCollectorInterface $reflector;

	public static function make(): ReflectionCollectorInterface
	{
		if (!isset(self::$reflector)) {
			self::$reflector = new ReflectionCollector();
		}

		return self::$reflector;
	}

	public function __invoke(): ReflectionCollectorInterface
	{
		return self::make();
	}
}