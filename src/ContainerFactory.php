<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Annotation\Factory;

/**
 * Containers only manage long-lived objects, and short-lived objects are managed by the caller.
 */
#[Factory]
final class ContainerFactory
{
	private static ?ContainerInterface $container = null;

	public function __invoke(): ContainerInterface
	{
		return self::$container ??= new Container();
	}
}