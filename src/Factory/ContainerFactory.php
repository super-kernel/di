<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Di\Annotation\Annotation;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Aop\Scanner\Scanner;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Exception\NotFoundException;

#[
	Factory,
	Annotation(
		[
			ContainerInterface::class,
			PsrContainerInterface::class,
		]
	),
]
final readonly class ContainerFactory
{
	/**
	 * @param Container $container
	 *
	 * @throws NotFoundException
	 */
	public function __construct(private Container $container)
	{
		$this->container->get(Scanner::class)->scan();
	}

	/**
	 * @return ContainerInterface
	 */
	public function __invoke(): ContainerInterface
	{
		return new Container();
	}
}