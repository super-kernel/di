<?php
declare(strict_types=1);

namespace SuperKernel\Di\Parser;

use PhpParser\Modifiers;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

/**
 * @ClassHandlerVisitor
 * @\SuperKernel\Di\Parser\ClassHandlerVisitor
 */
class ClassHandlerVisitor extends NodeVisitorAbstract
{

    protected $class;

    public function __construct(protected VisitorMetadata $visitorMetadata)
    {
    }

    public function enterNode(Node $node)
    {
    }

    public function leaveNode(Node $node)
    {
        var_dump($node);
        if ($node instanceof Class_) {

            $returnStmt = new Node\Stmt\Return_(
                new Node\Expr\New_(
                    new Class_(
                        null, // 匿名类没有名字
                        [
                            'stmts' => $node->stmts,
                        ]
                    ),
                    [
                        new Arg(
                            new FuncCall(
                                new Name([
                                    '... func_get_args'
                                ])
                            )
                        )
                    ],
                )
            );

//            // 将类替换为匿名类
            return new Node\Stmt\Class_(
                $node->name,
                [
                    'stmts' => [
                        new Node\Stmt\ClassMethod('__construct', [
                            'flags' => Modifiers::PUBLIC,
                            'params' => [
                                new Node\Param(
                                    new Node\Expr\Variable('args'),
                                )
                            ],
                            'stmts' => [$returnStmt],
                        ])
                    ],
//                    'stmts' => $node->stmts, // 保留类的方法和属性
//                    'extends' => $node->extends, // 保留父类
//                    'implements' => $node->implements, // 保留接口
                ]
            );
        }

        return null;
    }

    public function beforeTraverse(array $nodes)
    {
    }

    public function afterTraverse(array $nodes)
    {
    }
}