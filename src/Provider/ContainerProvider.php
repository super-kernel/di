<?php
declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\AttributeMetadataCollectorInterface;
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
	public function __invoke(?AttributeMetadataCollectorInterface $attributeMetadataCollector = null): PsrContainerInterface
	{
		$attributeMetadataCollector ??= new AttributeMetadataCollectorProvider()();

		return new Container($attributeMetadataCollector);
	}
}