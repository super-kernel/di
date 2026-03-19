<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use RuntimeException;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Di\Composer\Provider\PackageMetadataCollectorProvider;
use SuperKernel\Contract\AttributeMetadataCollectorInterface;
use SuperKernel\Contract\PackageMetadataCollectorInterface;
use SuperKernel\Contract\PathResolverInterface;
use SuperKernel\Contract\ProcessHandlerInterface;
use SuperKernel\Di\Collector\AttributeMetadataCollector;
use SuperKernel\Di\Factory\AttributeMetadataFactory;

#[
	Provider(AttributeMetadataCollectorInterface::class),
	Factory,
]
final class AttributeMetadataCollectorProvider
{
	private static AttributeMetadataCollectorInterface $attributeCollector;

	public function __invoke(
		?PathResolverInterface             $pathResolver = null,
		?ProcessHandlerInterface           $processHandler = null,
		?PackageMetadataCollectorInterface $packageMetadataCollector = null,
	): AttributeMetadataCollectorInterface
	{
		if (!isset(self::$attributeCollector)) {
			$pathResolver ??= new PathResolverProvider()();
			$processHandler ??= new PharProcessHandlerProvider()();
			$packageMetadataCollector ??= new PackageMetadataCollectorProvider()($pathResolver, $processHandler);

			$dir = $pathResolver->to('vendor')->to('.super-kernel')->to('attribute')->get();
			if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
				throw new RuntimeException("Could not create cache dir: $dir");
			}

			$attributeMetadataFactory = new AttributeMetadataFactory($pathResolver, $processHandler);
			$packageMetadataCollection = [];
			foreach ($packageMetadataCollector->getPackages() as $package) {
				$packageMetadataCollection[] = $attributeMetadataFactory->makeAttributeMetadata($package);
			}

			self::$attributeCollector = new AttributeMetadataCollector($pathResolver, $processHandler, ...$packageMetadataCollection);
		}

		return self::$attributeCollector;
	}
}