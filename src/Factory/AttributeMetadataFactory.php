<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Phar;
use Reflector;
use RuntimeException;
use SuperKernel\Attribute\Attribute;
use SuperKernel\Attribute\AttributeMetadata;
use SuperKernel\Attribute\Builder\AttributeMetadataBuilder;
use SuperKernel\ComposerResolver\Provider\PackageCollectorProvider;
use SuperKernel\Contract\PackageMetadataInterface;
use SuperKernel\Contract\PathResolverInterface;
use SuperKernel\Contract\ProcessHandlerInterface;
use SuperKernel\Reflector\ReflectionManager;
use Throwable;

final readonly class AttributeMetadataFactory
{
	public function __construct(
		private PathResolverInterface   $pathResolver,
		private ProcessHandlerInterface $processHandler,
	)
	{
	}

	public function makeAttributeMetadata(PackageMetadataInterface $package): ?AttributeMetadata
	{
		$fileName = str_replace(['/', '\\'], '_', $package->getName());
		$filePath = $this->pathResolver->to($fileName)->get();

		$isPhar = strlen(Phar::running(false)) > 0;
		if ($isPhar) {
			return $this->loadCache($filePath);
		}

		if (is_null($package->getReference())) {
			return $this->scan($package, $filePath);
		}

		$cachePackage = $this->loadCache($filePath);
		if ($cachePackage?->getReference() !== $package->getReference()) {
			return $this->scan($package, $filePath);
		}

		return $cachePackage;
	}

	private function loadCache(string $filePath): ?AttributeMetadata
	{
		if (!is_file($filePath)) return null;
		$content = file_get_contents($filePath);
		if (!$content) return null;

		$data = unserialize($content, ['allowed_classes' => [AttributeMetadata::class, Attribute::class]]);
		return $data instanceof AttributeMetadata ? $data : null;
	}

	private function scan(PackageMetadataInterface $package, string $filePath): ?AttributeMetadata
	{
		try {
			$this->processHandler->execute(function () use ($package, $filePath) {
				$metadata = $this->make($package);
				file_put_contents($filePath, serialize($metadata), LOCK_EX);
			});
		}
		catch (Throwable) {
			return null;
		}

		return $this->loadCache($filePath);
	}

	public function make(PackageMetadataInterface $packageMetadata): AttributeMetadata
	{
		$attributes = [];
		foreach ($packageMetadata->getClassmap() as $class => $path) {
			try {
				$reflectClass = ReflectionManager::reflectClass($class);

				$attributes[] = $this->addAttribute($reflectClass);
				$attributes[] = $this->addAttribute($reflectClass->getMethods());
				$attributes[] = $this->addAttribute($reflectClass->getProperties());
			}
			catch (Throwable $throwable) {
				if (!is_null($packageMetadata->getReference())) {
					continue;
				}

				printf("\033[33m[WARNING]\033[0m %s in %s" . PHP_EOL,
				       $throwable->getMessage(),
				       PackageCollectorProvider::make()
					       ->getPackage($packageMetadata->getName())
					       ->getPathResolver()
					       ->to($path)
					       ->get(),
				);
			}
		}

		return new AttributeMetadata(
			   $packageMetadata->getName(),
			   $packageMetadata->getReference(),
			...$attributes,
		);
	}

	public function addAttribute(array|Reflector $reflector): array
	{
		$reflectors = $reflector instanceof Reflector ? [$reflector] : $reflector;

		$attributes = [];
		foreach ($reflectors as $reflector) {
			if (!method_exists($reflector, 'getAttributes')) {
				continue;
			}
			foreach ($reflector->getAttributes() as $attribute) {
				$attributes[] = new Attribute($reflector, $attribute);
			}
		}

		return $attributes;
	}
}