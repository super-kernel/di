<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner;

use Closure;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Phar;
use Symfony\Component\Finder\Finder;
use function array_merge;
use function dirname;
use function file_exists;
use function file_get_contents;
use function is_file;
use function is_readable;
use function json_decode;
use function json_validate;
use function reset;
use function sprintf;
use function strlen;

final class ConfigProvider
{
	private ?ClassLoader $classLoader = null {
		get => $this->classLoader ??= (function () {
			$loaders = ClassLoader::getRegisteredLoaders();

			return reset($loaders);
		})();
	}

	private ?bool $pharEnable = null {
		get => $this->pharEnable ?? !!strlen(Phar::running(false));
	}

	private ?array $composerJson = null {
		get => $this->composerJson ??= (function () {
			if (!class_exists(InstalledVersions::class)) {
				throw new LogicException('Class InstalledVersions not found — composer runtime files are missing.');
			}

			$composerJsonFilepath = InstalledVersions::getRootPackage()['install_path'] . DIRECTORY_SEPARATOR . 'composer.json';

			if (!file_exists($composerJsonFilepath)) {
				throw new InvalidArgumentException(sprintf('The "%s" does not exist.', $composerJsonFilepath));
			}

			if (!is_file($composerJsonFilepath)) {
				throw new InvalidArgumentException(sprintf('The "%s" is not a file.', $composerJsonFilepath));
			}

			if (!is_readable($composerJsonFilepath)) {
				throw new InvalidArgumentException(sprintf('The "%s" is not readable.', $composerJsonFilepath));
			}

			$composerJsonContent = file_get_contents($composerJsonFilepath);

			if (!json_validate($composerJsonContent)) {
				throw new LogicException("composer.json is not valid JSON: $composerJsonFilepath");
			}

			try {
				$composerJson = json_decode($composerJsonContent, true, flags: JSON_THROW_ON_ERROR);
			}
			catch (JsonException $jsonException) {
				throw new LogicException("Failed to decode composer.json: " . $jsonException->getMessage(), previous: $jsonException);
			}

			return $composerJson;
		})();
	}

	public function getClassLoader(): ClassLoader
	{
		return $this->classLoader;
	}

	public function getRootPath(): string
	{
		return dirname(Closure::bind(fn() => $this->vendorDir, $this->classLoader, ClassLoader::class)());
	}

	public function isPharEnabled(): bool
	{
		return $this->pharEnable;
	}

	/**
	 * Get a Finder for all class map.
	 *
	 * @return Finder
	 */
	public function getFinder(): Finder
	{
		$psr4      = array_merge($this->composerJson['autoload']['psr-4'] ?? [], $this->composerJson['autoload-dev']['psr-4'] ?? []);
		$dirs      = array_map(fn($value) => $this->getRootPath() . DIRECTORY_SEPARATOR . $value, $psr4);
		$finder    = new Finder()->files()->name('*.php')->in($dirs);
		$libraries = InstalledVersions::getInstalledPackagesByType('library');

		foreach ($libraries as $library) {
			$libraryPath = InstalledVersions::getInstallPath($library);

			if (!$libraryPath || !is_dir($libraryPath)) {
				continue;
			}

			$finder = $finder->in($libraryPath);
		}

		return $finder;
	}
}