<?php
declare(strict_types=1);

namespace SuperKernel\Di\Support;

use Composer\Autoload\ClassLoader;
use Phar;
use RuntimeException;
use SuperKernel\Contract\ConfigProviderInterface;
use SuperKernel\Di\Collector\ReflectionManager;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_readable;
use function json_decode;
use function json_validate;
use function reset;

/**
 * Take over the class loading mechanism of composer to better serve developers.
 *
 * @Composer
 * @\SuperKernel\Di\Composer\Composer
 *
 * @method static array configProviders()
 */
final class Composer
{
	private static ?object $composer = null;

	public function __get(string $name)
	{
	}

	public static function __callStatic(string $name, array $arguments)
	{
		return self::$composer ??= new class {
			private ?ClassLoader $classLoader = null {
				get => $this->classLoader ??= (function () {
					$classLoader = ClassLoader::getRegisteredLoaders();
					return $this->classLoader = reset($classLoader);
				})();
			}

			private ?string $vendorDir = null {
				get => $this->vendorDir ??= (fn() => $this->vendorDir)->call($this->classLoader);
			}

			private ?string $rootPath = null {
				get => $this->rootPath ??= dirname(Phar::running(false) ?: $this->vendorDir);
			}

			private readonly array $installedContent;

			private ?array $jsonContent = null {
				get => $this->jsonContent ??= (function () {
					$path = $this->vendorDir . '/composer.json';

					if (!file_exists($path)) {
						throw new RuntimeException('composer.json file does not exist !');
					}
					if (!is_readable($path)) {
						throw new RuntimeException('composer.json file is not readable !');
					}

					$content = file_get_contents($path);

					if (!json_validate($content)) {
						throw new RuntimeException('state mismatch (invalid or malformed JSON).');
					}

					return $this->jsonContent = json_decode($content, true);
				})();
			}

			private array $extra;
			private array $script;
			private array $versions;

			private ?array $configProviders = null;

			public function getClassLoader(): ClassLoader
			{
				return $this->classLoader;
			}

			public function getVendorDir(): string
			{
				return $this->vendorDir;
			}

			public function getRootPath(): string
			{
				return $this->rootPath;
			}

			public function InstalledContent(): array
			{
				return $this->installedContent;
			}

			public function getMergedExtra(?string $key = null): array
			{
				if (null === $key) {
					return $this->extra;
				}

				$extra = [];

				foreach ($this->extra as $config) {
					if (!$configs = $config[$key] ?? null) {
						continue;
					}

					foreach ($configs as $k => $v) {
						$extra[$k] = array_merge($extra[$k] ?? [], is_array($v) ? $v : [$v]);
					}
				}

				return $extra;
			}

			public function __construct()
			{
				$path = $this->vendorDir . '/composer/installed.json';

				if (!file_exists($path)) {
					throw new RuntimeException('install.json file does not exist !');
				}
				if (!is_readable($path)) {
					throw new RuntimeException('install.json file is not readable !');
				}

				$installedContent = file_get_contents($path);

				if (!json_validate($installedContent)) {
					throw new RuntimeException('State mismatch (invalid or malformed JSON).');
				}

				$installedJsonContent = json_decode($installedContent, true);

				$packages    = $installedJsonContent['packages'] ?? [];
				$devPackages = $installedJsonContent['packages-dev'] ?? [];

				foreach ($packages + $devPackages as $package) {
					if (!$packageName = $package['name']) {
						continue;
					}

					foreach ($package as $key => $value) {
						match ($key) {
							'extra'   => $this->extra[$packageName] = $value,
							'script'  => $this->script[$packageName] = $value,
							'version' => $this->versions[$packageName] = $value,
							default   => null,
						};
					}
				}

				return $this->installedContent = $installedJsonContent;
			}

			public function configProviders(): array
			{
				if (null !== $this->configProviders) {
					return $this->configProviders;
				}

				$providerConfigs = [];

				foreach ($this->getMergedExtra('super-kernel')['config'] ?? [] as $configProvider) {
					if (
						is_string($configProvider)
						&& class_exists($configProvider)
						&& ReflectionManager::reflectClass($configProvider)->implementsInterface(ConfigProviderInterface::class)
					) {
						$providerConfigs[] = new $configProvider()();
					}
				}

				return $this->configProviders = array_merge_recursive(...$providerConfigs);
			}
		}->{$name}(...$arguments);
	}
}