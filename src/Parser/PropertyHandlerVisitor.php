<?php
declare(strict_types=1);

namespace SuperKernel\Di\Parser;

use PhpParser\NodeVisitorAbstract;

/**
 * @PropertyHandlerVisitor
 * @\SuperKernel\Di\Parser\PropertyHandlerVisitor
 */
class PropertyHandlerVisitor extends NodeVisitorAbstract
{
    public function __construct(protected VisitorMetadata $visitorMetadata)
    {
    }
}