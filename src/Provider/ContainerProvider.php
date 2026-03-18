<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Container;

#[
	Provider(PsrContainerInterface::class),
	Provider(ContainerInterface::class),
	Provider(Container::class),
	Factory,
]
final class ContainerProvider
{
	private static PsrContainerInterface $container;

	public function __invoke(): PsrContainerInterface
	{
		if (!isset(self::$container)) {
			self::$container = new Container();
		}

		return self::$container;
	}
}