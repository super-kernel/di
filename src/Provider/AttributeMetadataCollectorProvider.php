<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\AttributeMetadataCollectorInterface;
use SuperKernel\Contract\PackageMetadataCollectorInterface;
use SuperKernel\Contract\PathResolverInterface;
use SuperKernel\Contract\ProcessHandlerInterface;
use SuperKernel\Di\Collector\AttributeMetadataCollector;

#[
	Provider(AttributeMetadataCollectorInterface::class),
	Factory,
]
final class AttributeMetadataCollectorProvider
{
	private static AttributeMetadataCollectorInterface $attributeCollector;

	public function __invoke(
		PathResolverInterface             $pathResolver,
		ProcessHandlerInterface           $processHandler,
		PackageMetadataCollectorInterface $packageMetadataCollector,
	): AttributeMetadataCollectorInterface
	{
		if (!isset(self::$attributeCollector)) {
			self::$attributeCollector = new AttributeMetadataCollector($pathResolver, $processHandler, $packageMetadataCollector);
		}

		return self::$attributeCollector;
	}
}