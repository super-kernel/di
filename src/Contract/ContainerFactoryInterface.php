<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use Psr\Container\ContainerInterface;

/**
 * @ContainerFactoryInterface
 * @\SuperKernel\Di\Contract\ContainerFactoryInterface
 */
interface ContainerFactoryInterface
{
	/**
	 * In principle, this method will not throw exceptions unless the creation process of the container is taken over
	 * by an external party.
	 *
	 * @return ContainerInterface
	 */
	public function __invoke(): ContainerInterface;
}