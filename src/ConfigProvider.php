<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Di\Contract\ConfigProviderInterface;
use SuperKernel\Di\Aop\ScannerFactory;
use SuperKernel\Di\Contract\ScannerInterface;

final class ConfigProvider
{
	public function __invoke(): array
	{
		return [
			'dependencies' => [
				ContainerInterface::class    => ContainerFactory::class,
				PsrContainerInterface::class => ContainerFactory::class,
				'factories'                  => [
					ConfigProviderInterface::class => ConfigProviderFactory::class,
					ScannerInterface::class        => ScannerFactory::class,
				],
			],
		];
	}

	public function __construct()
	{
	}
}