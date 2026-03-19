<?php

declare(strict_types=1);

namespace SuperKernel\Di\Autoloader;

use RuntimeException;
use SuperKernel\Contract\ClassAutoloaderInterface;
use function array_merge;
use function spl_autoload_register;
use function spl_autoload_unregister;

/**
 * High-performance Static Class Autoloader for SuperKernel.
 *
 * This loader provides a mandatory, high-speed lookup mechanism using a pre-defined class map.
 * It is designed for production environments to bypass expensive PSR-4 filesystem checks
 * by providing O(1) resolution for core framework components.
 *
 * @api
 */
final class ClassAutoloader implements ClassAutoloaderInterface
{
	/**
	 * @var array<string, string> $classMap Associative array where key is FQCN and value is absolute path.
	 */
	private array $classMap;

	/**
	 * Initializes the autoloader with the core SuperKernel class map.
	 */
	public function __construct()
	{
		$this->classMap = ClassMapper::getClassMap();
	}

	public function addClassMap(array $classMap): void
	{
		$this->classMap = array_merge($this->classMap, $classMap);
	}

	public function register(): void
	{
		if (!spl_autoload_register([$this, '__autoload'], true, true)) {
			throw new RuntimeException('Failed to register ClassAutoloader to the top of the SPL stack.');
		}
	}

	public function unregister(): void
	{
		spl_autoload_unregister([$this, '__autoload']);
	}

	/**
	 * Resolves the class name to its corresponding file path using the internal map.
	 *
	 * This method provides the primary resolution logic for the SPL autoloader mechanism.
	 * It ensures an O(1) lookup and avoids redundant filesystem I/O.
	 *
	 * @param string $class The fully qualified class name.
	 *
	 * @return void
	 * @internal This method is for SPL callback use only.
	 */
	public function __autoload(string $class): void
	{
		if (isset($this->classMap[$class])) {
			include $this->classMap[$class];
		}
	}
}