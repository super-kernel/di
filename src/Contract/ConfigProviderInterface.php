<?php
declare(strict_types=1);

namespace SuperKernel\Di\Contract;

use Composer\Autoload\ClassLoader;

/**
 * @ConfigProviderInterface
 * @\SuperKernel\Di\Contract\ConfigProviderInterface
 */
interface ConfigProviderInterface
{
	public function getClassLoader(): ClassLoader;

	public function getRootPath(): string;

	public function getRootPackage(): array;

	public function getAllPackages(): array;

	public function getAllProviders(): array;

}