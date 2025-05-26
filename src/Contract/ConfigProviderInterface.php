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
	public function get(?string $key = null, mixed $default = null): mixed;

	public function getClassLoader(): ClassLoader;

	public function getRootPath(): string;
}