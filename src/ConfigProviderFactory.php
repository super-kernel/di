<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use Closure;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Exception;
use SuperKernel\Di\Contract\ConfigProviderInterface;

/**
 * @ConfigProviderFactory
 * @\SuperKernel\Di\ConfigProviderFactory
 */
final class ConfigProviderFactory
{
	private static ?self $container = null;

	private ?ConfigProviderInterface $configProvider = null {
		get => $this->configProvider ??= new class implements ConfigProviderInterface {
			private ?array $configProviders = null {
				get => $this->configProviders ??= (function () {
					$providerConfigs = [];

					$packages = array_merge(InstalledVersions::getAllRawData()[0]['versions'] ?? [], InstalledVersions::getAllRawData()[0]['root'] ?? []);

					foreach ($packages as $packageName => $package) {
						$configProvider = $package['extra']['super-kernel']['config'] ?? null;
						if ($configProvider) {
							$providerConfigs[$packageName] = new $configProvider()();
						}
					}

					return $providerConfigs;
				})();
			}

			private ?array $configs = null {
				get => $this->configs ??= (function () {
					$configs = [];
					foreach ($this->configProviders as $configProvider) {
						foreach ($configProvider as $key => $value) {
							if (is_array($value)) {
								$configs[$key] = array_merge($configs[$key] ?? [], $value);
							} else {
								$configs[$key][] = $value;
							}
						}
					}

					return $configs;
				})();
			}

			private ?ClassLoader $classLoader = null {
				get => $this->classLoader ??= (function () {
					$loaders = ClassLoader::getRegisteredLoaders();

					return reset($loaders);
				})();
			}

			public function get(?string $key = null, mixed $default = null): mixed
			{
				if (is_null($key)) {
					return $this->configs;
				}

				return $this->configs[$key] ?? $default;
			}

			public function getClassLoader(): ClassLoader
			{
				return $this->classLoader;
			}

			public function getRootPath(): string
			{
				return dirname(Closure::bind(fn() => $this->vendorDir, $this->classLoader, ClassLoader::class)());
			}
		};
	}

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		if (!InstalledVersions::isInstalled('super-kernel/composer-plugin')) {
			throw new Exception(<<<ERROR
				[ERROR] Missing required dependency: super-kernel/composer-plugin

				Please install it using Composer:
                    composer require super-kernel/composer-plugin --dev

				This plugin is essential for the framework to work properly.
ERROR,
			);
		}
	}

	public function __invoke(): ConfigProviderInterface
	{
		return (self::$container ??= $this)->configProvider;
	}
}