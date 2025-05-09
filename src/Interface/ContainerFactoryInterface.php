<?php
declare(strict_types=1);

namespace SuperKernel\Di\Interface;

use Psr\Container\ContainerInterface;
use SuperKernel\Contract\ComposerInterface;

/**
 * @ContainerFactoryInterface
 * @\SuperKernel\Di\Interface\ContainerFactoryInterface
 */
interface ContainerFactoryInterface
{
	public function __construct(?ComposerInterface $composer = null);

	/**
	 * In principle, this method will not throw exceptions unless the creation process of the container is taken over
	 * by an external party.
	 *
	 * @return ContainerInterface
	 */
	public function __invoke(): ContainerInterface;
}