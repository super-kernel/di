<?php
declare(strict_types=1);

namespace SuperKernelTest\Di\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SuperKernel\Annotation\Factory;
use SuperKernel\Annotation\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernelTest\Di\Application;

#[
	Provider(ApplicationInterface::class),
	Factory,
]
final class ApplicationProvider
{
	/**
	 * @param ContainerInterface $container
	 *
	 * @return ApplicationInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __invoke(ContainerInterface $container): ApplicationInterface
	{
		return $container->get(Application::class);
	}
}