<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Psr\Container\ContainerInterface;
use SuperKernel\Contract\ConfigProviderInterface;
use SuperKernel\Di\Aop\ScannerHandler\ScanHandlerFactory;
use SuperKernel\Di\Composer\ComposerFactory;
use SuperKernel\Di\Interface\ComposerInterface;
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
				ContainerInterface::class   => Container::class,
				ComposerInterface::class    => ComposerFactory::class,
				ScanHandlerInterface::class => ScanHandlerFactory::class,
			],
		];
	}
}