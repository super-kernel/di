<?php
declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Phar;
use Reflector;
use SuperKernel\ComposerResolver\Provider\PackageCollectorProvider;
use SuperKernel\Contract\AttributeMetadataInterface;
use SuperKernel\Contract\PackageMetadataInterface;
use SuperKernel\Contract\PathResolverInterface;
use SuperKernel\Contract\ProcessHandlerInterface;
use SuperKernel\Di\Collector\AttributeMetadata;
use SuperKernel\Di\Collector\PackageAttributeMetadata;
use SuperKernel\Di\Provider\ReflectionCollectorProvider;
use Throwable;
use function array_filter;
use function array_merge;

final readonly class AttributeMetadataFactory
{
	public function __construct(
		private PathResolverInterface   $pathResolver,
		private ProcessHandlerInterface $processHandler,
	)
	{
	}

	public function makeAttributeMetadata(PackageMetadataInterface $package): ?PackageAttributeMetadata
	{
		$fileName = str_replace(['/', '\\'], '_', $package->getName());
		$filePath = $this->pathResolver->to($fileName)->get();

//		return $this->scan($package, $filePath);

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

	private function loadCache(string $filePath): ?PackageAttributeMetadata
	{
		if (!is_file($filePath)) return null;
		$content = file_get_contents($filePath);
		if (!$content) return null;

		$data = unserialize($content, ['allowed_classes' => [PackageAttributeMetadata::class, AttributeMetadata::class]]);
		return $data instanceof PackageAttributeMetadata ? $data : null;
	}

	private function scan(PackageMetadataInterface $package, string $filePath): ?PackageAttributeMetadata
	{
		try {
			$this->processHandler->execute(function () use ($package, $filePath) {
				$metadata = $this->make($package);
				file_put_contents($filePath, serialize($metadata), LOCK_EX);
			});
		}
		catch (Throwable $throwable) {
			var_dump($throwable->getMessage());
			return null;
		}

		return $this->loadCache($filePath);
	}

	public function make(PackageMetadataInterface $packageMetadata): PackageAttributeMetadata
	{
		$attributes = [];
		foreach ($packageMetadata->getClassmap() as $class => $path) {
			try {
				$reflectClass = new ReflectionCollectorProvider()()->reflectClass($class);

				$attributes = array_merge($attributes, $this->addAttribute($reflectClass));
				$attributes = array_merge($attributes, $this->addAttribute($reflectClass->getMethods()));
				$attributes = array_merge($attributes, $this->addAttribute($reflectClass->getProperties()));
			}
			catch (Throwable $throwable) {
				if (!is_null($packageMetadata->getReference())) {
					continue;
				}

				printf("\033[33m[WARNING]\033[0m %s in %s" . PHP_EOL,
				       $throwable->getMessage(),
				       PackageCollectorProvider::make($this->pathResolver)
					       ->getPackage($packageMetadata->getName())
					       ->getPathResolver()
					       ->to($path)
					       ->get(),
				);
			}
		}

		return new PackageAttributeMetadata(
			   $packageMetadata->getName(),
			   $packageMetadata->getReference(),
			...array_filter($attributes, fn($attribute) => $attribute instanceof AttributeMetadataInterface),
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
				$attributes[] = new AttributeMetadata($reflector, $attribute);
			}
		}

		return $attributes;
	}
}