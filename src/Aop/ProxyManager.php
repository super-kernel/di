<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop;

use Error;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/**
 * @ProxyManager
 * @\SuperKernel\Di\Aop\ProxyManager
 */
final class ProxyManager
{
	private array $proxies = [];

	public function __construct(private array $classMap)
	{
		$this->handle();
	}

	public function getProxies(): array
	{
		return $this->proxies;
	}

	private function handle(): void
	{
		$traverser = new NodeTraverser();

		$traverser->addVisitor(new class extends NodeVisitorAbstract {
			public function enterNode(Node $node)
			{
//				if ($node instanceof \PhpParser\Node\Stmt\Property) {
////					var_dump($node->props[0]->name);
//				}
				if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
//					var_dump($node->name);
				}
//				var_dump(get_class($node));
//					if ($node instanceof Function_) {
//						// Clean out the function body
//						$node->stmts = [];
//					}
			}

			public function afterTraverse(array $nodes)
			{
				foreach ($nodes as $node) {

					if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
						foreach ($node as $stmts) {
							if (is_array($stmts)) {
								foreach ($stmts as $stmt) {
									if ($stmt instanceof \PhpParser\Node\Stmt\Class_) {
//										var_dump($stmt);
										$constructor = $stmt->getMethod('__construct');

										if (null === $constructor) {
											$newNode = new Node\Stmt\ClassMethod('__construct');

											$newNode->flags = Modifiers::PUBLIC;
											$newNode->params = [];

											$newNode->stmts = [

											];

											$stmts[] = $newNode;
										}
									}

								}
							} else {
//								var_dump($stmts);
							}
						}
					}

				}
			}
		});


		foreach ($this->classMap as $class => $path) {

			$code = file_get_contents($path);

			$parser = new ParserFactory()->createForNewestSupportedVersion();
			try {
				$ast = $parser->parse($code);
			}
			catch (Error $error) {
				echo "Parse error: {$error->getMessage()}\n";
				return;
			}

			$traverser->traverse($ast);

//			$dumper = new NodeDumper();
//			echo $dumper->dump($ast) . "\n";

			break;
		}
	}
}