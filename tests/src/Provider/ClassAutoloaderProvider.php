<?php
declare(strict_types=1);

namespace SuperKernelTest\Di\Provider;

use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ClassAutoloaderInterface;
use SuperKernel\Di\Autoloader\ClassAutoloader;

#[
	Provider(ClassAutoloaderInterface::class),
	Factory,
]
final class ClassAutoloaderProvider
{
	private static ClassAutoloaderInterface $classAutoloader;

	public function __invoke(): ClassAutoloaderInterface
	{
		if (self::$classAutoloader === null) {
			self::$classAutoloader = new ClassAutoloader();
		}

		return self::$classAutoloader;
	}
}