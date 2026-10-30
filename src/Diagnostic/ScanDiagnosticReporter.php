<?php

declare(strict_types=1);

namespace SuperKernel\Di\Diagnostic;

use Psr\Container\ContainerInterface;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Di\Exception\NotFoundException;
use Throwable;

use function count;
use function ord;
use function preg_replace_callback;
use function sprintf;

/**
 * Logs captured annotation scan diagnostics through a container's optional stdout logger.
 *
 * @internal
 */
final class ScanDiagnosticReporter
{
    /**
     * @param list<array{
     *     sequence: int, package: string, class: string, exception_type: string, message: string
     * }> $diagnostics
     *
     * @return bool Whether the batch was handed to a logger (or was empty); false requests emergency fallback
     *
     * @throws Throwable When the configured logger cannot be resolved or cannot accept a record
     */
    public static function report(ContainerInterface $container, array $diagnostics): bool
    {
        if ([] === $diagnostics) {
            return true;
        }

        try {
            $logger = $container->get(StdoutLoggerInterface::class);
        } catch (NotFoundException $exception) {
            if (StdoutLoggerInterface::class !== $exception->identifier()) {
                throw $exception;
            }

            return false;
        }

        $count = count($diagnostics);
        $logger->warning(
            1 === $count ? 'Annotation scan skipped 1 class.' : sprintf('Annotation scan skipped %d classes.', $count),
            [
                'event'             => 'annotation_scan.skipped_summary',
                'skipped_count'     => $count,
                'diagnostic_source' => 'annotation_cache',
            ],
        );
        $display = static fn(string $value): string => preg_replace_callback(
            '/[\x00-\x1f\x7f]/',
            static fn(array $match): string => match ($match[0]) {
                "\n"    => '\\n',
                "\r"    => '\\r',
                "\t"    => '\\t',
                default => sprintf('\\x%02X', ord($match[0])),
            },
            $value,
        );

        foreach ($diagnostics as $diagnostic) {
            $logger->info(sprintf(
                'Annotation scan skipped %s: %s: %s',
                $display($diagnostic['class']),
                $display($diagnostic['exception_type']),
                $display($diagnostic['message']),
            ), [
                'event'             => 'annotation_scan.skipped_class',
                'diagnostic_source' => 'annotation_cache',
                'sequence'          => $diagnostic['sequence'],
                'package'           => $diagnostic['package'],
                'class'             => $diagnostic['class'],
                'exception_type'    => $diagnostic['exception_type'],
                'exception_message' => $diagnostic['message'],
            ]);
        }

        return true;
    }
}
