<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use Composer\Autoload\ClassLoader;
use SuperKernel\Di\Abstract\CollectorAbstract;

final class ScannerCollectorAbstract extends CollectorAbstract
{
	public function __construct(private readonly ClassLoader $classLoader)
	{
		$this->scanClass();
	}

	private function scanClass(): void
	{
		$classMap = $this->classLoader->getClassMap();

		foreach ($classMap as $class => $path) {
			if ($this->isInVendorDir($path)) {
				continue;
			}
			$attributes = $this->getClassAttributes($class);
			$this->processAttributes($class, $attributes);
		}
	}

	/**
	 * 检查类文件是否在 vendor 目录中
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	private function isInVendorDir(string $path): bool
	{
		return str_starts_with(realpath($path), ComposerFactory::getVendorDir());
	}

	/**
	 * 获取类的属性
	 *
	 * @param string $class
	 *
	 * @return array
	 */
	private function getClassAttributes(string $class): array
	{
		return ReflectionManager::reflectClass($class)->getAttributes();
	}

	/**
	 * 处理类的属性并根据需要进行存储
	 *
	 * @param string $class
	 * @param array  $attributes
	 */
	private function processAttributes(string $class, array $attributes): void
	{
		foreach ($attributes as $attribute) {
			if (new ReflectionManager()->reflectClass($attribute->getName())->isUserDefined()) {
				self::set($attribute->getName(), $class);
			}
		}
	}

	public function getDependencies(): array
	{
		return [];
	}
}