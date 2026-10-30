<?php

declare(strict_types=1);

namespace SuperKernel\Di\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use SuperKernel\Contract\AnnotationCollectorInterface;

/**
 * Obtains the root container's reserved annotation collector for definer, resolver, and property-handler discovery.
 *
 * @internal
 */
final class ReservedCollector
{
    /** @throws ContainerExceptionInterface When the annotation collector cannot be resolved */
    public static function from(ContainerInterface $container): AnnotationCollectorInterface
    {
        return $container->get(AnnotationCollectorInterface::class);
    }
}
