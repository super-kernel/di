<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Annotation\Annotation;
use SuperKernel\Di\Annotation\Configuration;
use SuperKernel\Di\Annotation\Definer;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Annotation\Inject;
use SuperKernel\Di\Annotation\Resolver;
use SuperKernel\Di\Contract\ConfigProviderInterface;
use SuperKernel\Di\Contract\DefinerInterface;
use SuperKernel\Di\Factory\ConfigProviderFactory;
use SuperKernel\Di\Factory\ContainerFactory;
use SuperKernel\Di\Factory\DefinerFactory;

final class ConfigProvider
{
	public function __invoke(): array
	{
		return [
			'annotation'   => [
				Annotation::class,
				Configuration::class,
				Definer::class,
				Factory::class,
				Inject::class,
				Resolver::class,
			],
			'dependencies' => [
				'factories'                  => [
					ConfigProviderInterface::class => ConfigProviderFactory::class,
					DefinerInterface::class        => DefinerFactory::class,
				],
				Container::class             => ContainerFactory::class,
				ContainerInterface::class    => ContainerFactory::class,
				PsrContainerInterface::class => ContainerFactory::class,
			],
		];
	}
}