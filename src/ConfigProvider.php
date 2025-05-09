<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SuperKernel\Di\Aop\ScannerHandler\ScanHandlerFactory;
use SuperKernel\Di\Interface\ScanHandlerInterface;

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
				ScanHandlerInterface::class => ScanHandlerFactory::class,
			],
		];
	}
}