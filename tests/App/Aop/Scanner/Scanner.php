<?php
declare(strict_types=1);

namespace Tests\App\Aop\Scanner;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\ScanHandlerInterface;
use SuperKernel\Di\Exception\NotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Tests\App\Factory\ScanHandlerFactory;

final class Scanner
{
	private readonly ScanHandlerInterface $handler;

	protected string $path;

	final protected Parser $parser;

	final protected readonly ConfigProvider $configProvider;

	/**
	 * @param Container      $container
	 * @param Filesystem     $filesystem
	 * @param ConfigProvider $configProvider
	 * @param ParserFactory  $parserFactory
	 *
	 * @throws NotFoundException
	 */
	public function __construct(
		private readonly Container  $container,
		private readonly Filesystem $filesystem,
		ConfigProvider              $configProvider,
		ParserFactory               $parserFactory,
	)
	{
		$this->path           = $configProvider->getRootPath() . '/runtime/container/scan.cache';
		$this->parser         = $parserFactory->createForHostVersion();
		$this->handler        = $this->container->get(ScanHandlerFactory::class);
		$this->configProvider = $configProvider;
	}

	public function scan(): array
	{
		$this->handler->scan();

		return [];
	}

	public function process(): void
	{
		foreach ($this->configProvider->getFinder() as $finder) {
			$ast = $this->parser->parse($finder->getContents());


			$nameResolver  = new NameResolver(null, [
				'preserveOriginalNames' => true,
				'replaceNodes'          => true,
			]);
			$nodeTraverser = new NodeTraverser();
			$nodeTraverser->addVisitor($nameResolver);
			$nodeTraverser->addVisitor(new class extends NodeVisitorAbstract {
				private string $className;

				public function enterNode(Node $node)
				{
					if ($node instanceof Node\Stmt\Class_) {
						var_dump($node->name->name);
					}
				}
			});

			// Resolve names
			$stmts = $nodeTraverser->traverse($ast);

//			var_dump(
//				[
//					$finder,
//					$nameResolver->getNameContext()->getNamespace()->name,
//					$nameResolver->getNameContext(),
//				],
//			);
			exit();
		}
	}

	private function putCache(mixed $data): void
	{
		if (!$this->filesystem->exists($dir = dirname($this->path))) {
			$this->filesystem->mkdir($dir, 0755);
		}

		$this->filesystem->dumpFile($this->path, $data);
	}
}