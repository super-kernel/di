<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use Psr\Container\ContainerInterface;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Attribute\Provider\AttributeCollectorProvider;
use SuperKernel\Di\Container;

#[
	Provider(ContainerInterface::class),
	Factory,
]
final class ContainerProvider
{
	private static ContainerInterface $container;

	public function __invoke(): ContainerInterface
	{
		if (!isset(self::$container)) {
			self::$container = new Container(
				new AttributeCollectorProvider()(),
			);
		}

		return self::$container;
	}
}