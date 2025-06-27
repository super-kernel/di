<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use SuperKernel\Di\Annotation\Configuration;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Aop\Scanner\ScanHandlerFactory;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ContainerInterface;
use SuperKernel\Di\Contract\ScanHandlerInterface;
use SuperKernel\Di\Exception\NotFoundException;

#[
	Factory,
	Configuration(ContainerInterface::class),
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
		$this->container->get(ScanHandlerFactory::class)->scan();
	}

	/**
	 * @return ContainerInterface
	 */
	public function __invoke(): ContainerInterface
	{
		return new Container();
	}
}