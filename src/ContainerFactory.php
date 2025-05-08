<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface;
use SuperKernel\Di\Abstract\AbstractContainerFactory;

/**
 * @ContainerFactory
 * @\SuperKernel\Di\ContainerFactory
 */
final class ContainerFactory extends AbstractContainerFactory
{
	public function __invoke(): ContainerInterface
	{
		return new Container($this);
	}
}