<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Di\Annotation\Annotation;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ContainerInterface;

#[
	Factory,
	Annotation(
		[
			Container::class,
			ContainerInterface::class,
			PsrContainerInterface::class,
		]
	),
]
final readonly class ContainerFactory
{
	/**
	 * @return ContainerInterface
	 */
	public function __invoke(): ContainerInterface
	{
		return new Container();
	}
}