<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use Composer\Autoload\ClassLoader;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use SuperKernel\Di\Contract\AttributeCollectorInterface;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function serialize;
use function str_replace;
use function unserialize;

final class PackageCollector
{
	private ?string $vendorDir = null {
		get => $this->vendorDir ?? (fn() => $this->vendorDir)->call($this->classLoader);
	}

	private ?string $path = null {
		get => $this->path ?? ($this->vendorDir . DIRECTORY_SEPARATOR . '.skernel');
	}

	private ?string $packageDir = null {
		get => $this->packageDir ?? ($this->path . DIRECTORY_SEPARATOR . 'packages');
	}

	private ?string $attributePath = null {
		get => $this->attributePath ?? ($this->path . DIRECTORY_SEPARATOR . 'attribute.cache');
	}

	private array $packages = [];

	public function __construct(private readonly ClassLoader $classLoader)
	{
	}

	public function collect(string $packageName): void
	{
		$path = $this->path . DIRECTORY_SEPARATOR . str_replace('/', '-', $packageName) . '.cache';

		$this->packages[$packageName] = $path;
	}

	public function scan(): void
	{
		$attributeCollector = new AttributeCollector();

		$parser    = new ParserFactory()->createForVersion(PhpVersion::getHostVersion());
		$traverser = new NodeTraverser();

		foreach ($this->packages as $packageName => $path) {
			$package = file_exists($path) ? new Package($packageName) : unserialize(file_get_contents($path));
			$package($attributeCollector, $parser, $traverser);
			file_put_contents($path, serialize($package));
		}

		file_put_contents($this->attributePath, serialize($attributeCollector));
	}

	public function __invoke(): AttributeCollectorInterface
	{
		return unserialize(file_get_contents($this->attributePath));
	}
}