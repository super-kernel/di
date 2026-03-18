<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\ComposerResolver\Provider\PackageMetadataCollectorProvider;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Collector\AttributeMetadataCollector;
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
			$pathResolver = new PathResolverProvider()();
			$processHandler = new PharProcessHandlerProvider()();
			$packageMetadataCollector = new PackageMetadataCollectorProvider()($pathResolver, $processHandler);
			$attributeMetadataCollector = new AttributeMetadataCollector($pathResolver, $processHandler, $packageMetadataCollector);
			self::$container = new Container($attributeMetadataCollector);
		}

		return self::$container;
	}
}