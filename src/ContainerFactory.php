<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface;
use SuperKernel\Di\Abstract\AbstractContainerFactory;
use SuperKernel\Di\Interface\ComposerInterface;

/**
 * @ContainerFactory
 * @\SuperKernel\Di\ContainerFactory
 */
final class ContainerFactory extends AbstractContainerFactory
{
	public function __invoke(): ContainerInterface
	{
		$container = new Container($this);

		/* @noinspection PhpUnhandledExceptionInspection */
		return null === $this->composer
			? new self($container->get(ComposerInterface::class))()
			: $container;
	}
}