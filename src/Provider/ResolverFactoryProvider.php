<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Factory\ResolverFactory;

#[
	Provider(ResolverFactoryInterface::class),
	Factory,
]
final class ResolverFactoryProvider
{
	private static ResolverFactoryInterface $resolverFactory;

	public function __invoke(ContainerInterface $container): ResolverFactoryInterface
	{
		if (!isset(self::$resolverFactory)) {
			self::$resolverFactory = new ResolverFactory($container);
		}
		return self::$resolverFactory;
	}
}