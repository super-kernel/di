<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Factory\DefinitionFactory;

#[
	Provider(DefinitionFactoryInterface::class),
	Factory,
]
final class DefinitionFactoryProvider
{
	private static DefinitionFactoryInterface $definitionFactory;

	public function __invoke(ContainerInterface $container): DefinitionFactoryInterface
	{
		if (!isset(self::$definitionFactory)) {
			self::$definitionFactory = new DefinitionFactory($container);
		}
		return self::$definitionFactory;
	}
}