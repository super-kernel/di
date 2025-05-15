<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Contract\ConfigProviderInterface;
use SuperKernel\Di\Aop\ScannerHandler\ScanHandlerFactory;
use SuperKernel\Di\Interface\ScanHandlerInterface;

/**
 * @ConfigProvider
 * @\SuperKernel\Di\ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
	public function __invoke(): array
	{
		return [
			'dependencies' => [
				ScanHandlerInterface::class => ScanHandlerFactory::class,
			],
		];
	}

	public function __construct()
	{
	}
}