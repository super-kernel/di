<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Visitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

/**
 * @ClassHandlerVisitor
 * @\SuperKernel\Di\Aop\Visitor\ClassHandlerVisitor
 */
final class ClassHandlerVisitor extends NodeVisitorAbstract
{
    private string $classname;

    private array $properties = [];

    private array $methods = [];

    private ?Name $extends = null;

    private array $stmts = [];


    public function __construct()
    {

    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
//            var_dump($node);
            $this->classname = $node->name->name;
            $this->extends = $node->extends;
            $this->stmts = $node->stmts;
            $node->stmts = [];
        }
        if ($node instanceof Property) {

            $this->properties [] = $node;
            $node = null;
        }
        if ($node instanceof ClassMethod) {
            $classMethod = clone $node;
            $classMethod->attrGroups = [];
            $this->methods [$node->name->name] = $classMethod;
        }
    }

    public function afterTraverse(array $nodes): array
    {
        $nodes [] = new Expression(
            new StaticCall(new Name('\SuperKernel\Di\Aop\ProxyManager'), 'insert', [
                new ClassConstFetch(new Name($this->classname), 'class'),
                new Arg(new Closure([
                    'stmts' => [
                        new Return_(
                            new New_(
                                new Class_(null, [
                                    'stmts' => $this->stmts,
                                    'extends' => $this->extends,
                                ]),
                            )
                        )
                    ],
                ])),
            ])
        );

        return $nodes;
    }
}