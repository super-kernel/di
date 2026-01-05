<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use Composer\InstalledVersions;
use FilesystemIterator;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SuperKernel\Di\Ast\Visitor\ClassnameExtractor;
use SuperKernel\Di\Contract\AttributeCollectorInterface;

final class Package
{
	private string $version;

	private array $classes = [];

	public function __construct(private readonly string $name)
	{
	}

	public function addClass(string $class, string $path): void
	{
		$this->classes[$class] = $path;
	}

	public function getClassMap(): array
	{
		return $this->classes;
	}

	public function __unserialize(array $data): void
	{
	}

	public function __serialize(): array
	{
		return $this->classes;
	}

	public function __invoke(
		AttributeCollectorInterface $attributeCollector,
		Parser                      $parser,
		NodeTraverser               $nodeTraverser,
	): void
	{
		$path = InstalledVersions::getInstallPath($this->name);

		if (!$path) return;

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
		);

		/* @var SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'php') {
				continue;
			}

			$ast = $parser->parse(file_get_contents($file->getRealPath()));

			$classnameExtractor = new ClassnameExtractor();

			$nodeTraverser->addVisitor($classnameExtractor);
			$nodeTraverser->traverse($ast);

			$this->classes[] = $classnameExtractor->getClassname();
		}

		exit();
	}
}