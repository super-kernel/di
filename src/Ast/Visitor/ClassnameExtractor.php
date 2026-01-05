<?php
declare(strict_types=1);

namespace SuperKernel\Di\Ast\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ClassnameExtractor extends NodeVisitorAbstract
{
	private ?string $namespace = null;

	private ?string $class = null;

	public function enterNode(Node $node): void
	{
		if ($node instanceof Node\Stmt\Namespace_) {
			$this->namespace = $node->name?->name;
		}

		if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_ || $node instanceof Node\Stmt\Trait_) {
			$this->class = $node->name?->name;
		}
	}

	public function getClassname(): ?string
	{
		if (null !== $this->class) {
			return $this->namespace . '\\' . $this->class;
		}

		return $this->class;
	}
}