<?php
declare(strict_types=1);

namespace SuperKernel\Di\Aop\Visitor;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;

/**
 * @VisitorMetadata
 * @\SuperKernel\Di\Parser\VisitorMetadata
 */
final class VisitorMetadata
{
    public bool $isFinalClassType = false;

    public string $simpleClassName;

    protected ClassLike $classLikes;

    /**
     * @param Stmt[] $stmts
     */
    public function __construct(public array $stmts)
    {
//        var_dump(
//            $stmts
//        );
//        foreach ($stmts as $stmt) {
//            if ($stmt instanceof Namespace_) {
//                foreach ($stmt->stmts as $node) {
//                    var_dump($node);
//                    //                    if ($node instanceof Declare_) {}
//                }
//            }
//        }
    }
}
