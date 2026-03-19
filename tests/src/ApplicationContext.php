<?php
declare(strict_types=1);

namespace SuperKernelTest\Di;

use RuntimeException;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Provider\ContainerProvider;
use Throwable;

final class ApplicationContext
{
	private static ContainerInterface $container;

	public static function getContainer(): ContainerInterface
	{
		if (!isset(self::$container)) {
			try {
				self::$container = new ContainerProvider()()->get(ContainerInterface::class);
			}
			catch (Throwable $throwable) {
				throw new RuntimeException($throwable->getMessage());
			}
		}
		return self::$container;
	}
}