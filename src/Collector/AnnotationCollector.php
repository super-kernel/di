<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Di\Abstract\AbstractCollector;

/**
 * @AnnotationCollector
 * @\SuperKernel\Di\Collector\AnnotationCollector
 */
final class AnnotationCollector extends AbstractCollector
{
	protected static array $collectors = [];

	public function getCollector()
	{

	}
}