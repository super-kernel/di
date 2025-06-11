<?php
declare(strict_types=1);

namespace SuperKernel\Di;

use SplPriorityQueue;
use SuperKernel\Di\Abstract\AbstractDefinitionFactory;
use SuperKernel\Di\Annotation\Factory;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\DefinitionInterface;
use SuperKernel\Di\Contract\ResolverInterface;

#[Factory]
final class DefinitionFactory extends AbstractDefinitionFactory implements DefinitionFactoryInterface
{
	public function __construct()
	{
	}
}