<?php
declare(strict_types=1);

namespace SuperKernel\Di\Collector;

use SuperKernel\Di\Abstract\CollectorAbstract;

/**
 * @AnnotationCollectorAbstract
 * @\SuperKernel\Di\Collector\AnnotationCollectorAbstract
 */
final class AnnotationCollectorAbstract extends CollectorAbstract
{
	protected static array $collectors = [];
}