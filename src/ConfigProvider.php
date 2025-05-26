<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Di\Contract\ConfigProviderInterface;
use SuperKernel\Di\Aop\ScannerFactory;
use SuperKernel\Di\Contract\ScannerInterface;

/**
 * @ConfigProvider
 * @\SuperKernel\Di\ConfigProvider
 */
final class ConfigProvider
{
	public function __invoke(): array
	{
		return [
			'dependencies' => [
				'factories' => [
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