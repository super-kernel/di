<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Visitor;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use SuperKernel\Parser\Collector\CollectorFactory;

/**
 * @ClassHandlerVisitor
 * @\SuperKernel\Di\Parser\ClassHandlerVisitor
 */
final class ParserHandler extends NodeVisitorAbstract
{
    private string $classname;
    private array $params;

    public function __construct(private CollectorFactory $collectorFactory)
    {
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Namespace_) {
            /**
             * Only one class is allowed to be defined in one file.
             * @var Class_ $class_
             */
            $class_ = reset($node->stmts);
            $this->classname = $class_->name->name;
        }
        if ($node instanceof ClassMethod && $node->name->name === '__construct') {
            $this->params = $node->params;
        }
    }

    /**
     * @param Node $node
     * @return Expression|void
     * @formatter:off
     */
    public function leaveNode(Node $node)
    {
        if ($node instanceof Class_) {
            return new Expression(
                new StaticCall(new Name('\SuperKernel\Parser\ProxyManager'), 'insert', [
                    new ClassConstFetch(new Name($this->classname), 'class'),
                    new Arg(new Closure([
                        'stmts' => [
                            new Return_(
                                new New_(
                                    new Class_(null, [
                                        'flags' => $node->flags,
                                        'stmts' => $node->stmts,
                                        'extends' => $node->extends,
                                        'implements' => $node->implements,
                                    ]),
                                    [
                                        new Arg(
                                            new FuncCall(
                                                new Name('func_get_args')
                                            ), false, true
                                        ),
                                    ],
                                )
                            )
                        ],
                    ])),
                ])
            );
        }
    }

    /** @formatter:on */

    public function beforeTraverse(array $nodes)
    {

    }

    public function afterTraverse(array $nodes)
    {
        $nodes [] = new Class_($this->classname, [
            'stmts' => [
                new ClassMethod('__construct', [
                    'flags' => Modifiers::PUBLIC,
                    'params' => $this->params,
                    'stmts' => [],
                ]),
            ],
        ]);

        return $nodes;
    }

    public function getProxyTemplate(array $stmts, ?array $extends, array $implements): Class_
    {
        return new Node\Stmt\Class_($this->classname, [
            'stmts' => [
                new Node\Stmt\ClassMethod('__construct', [
                    'flags' => Modifiers::PUBLIC,
                    'params' => $this->params,
                    'stmts' => [
                        new Node\Stmt\Return_(
                            new Node\Expr\New_(
                                new Class_(null, [
                                    'stmts' => $stmts,
                                    'extends' => $extends,
                                    'implements' => $implements,
                                ]),
                                [
                                    new Arg(
                                        new FuncCall(
                                            new Name(['... func_get_args'])
                                        )
                                    )
                                ],
                            )
                        )
                    ]
                ]),
            ],
        ]);
    }
}