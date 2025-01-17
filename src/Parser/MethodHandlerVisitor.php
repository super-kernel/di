<?php
declare(strict_types=1);

namespace SuperKernel\Di\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @MethodHandlerVisitor
 * @\SuperKernel\Di\Parser\MethodHandlerVisitor
 */
class MethodHandlerVisitor extends NodeVisitorAbstract
{
    public function __construct(protected VisitorMetadata $visitorMetadata)
    {
    }

    public function enterNode(Node $node)
    {

    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassMethod) {

            foreach ($node->stmts as $stmt) {
                var_dump($stmt);
            }
        }
    }
}