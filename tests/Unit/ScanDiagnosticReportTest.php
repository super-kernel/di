<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Di\Provider\ContainerProvider;
use Throwable;

final class ScanDiagnosticReportTest extends TestCase
{
    /** @throws Throwable When the reporting method fails */
    public function testReportEscapesOnlyDisplayControlBytesAndRetainsRawContext(): void
    {
        $raw = "a\r\n\t\033\x00\x7f\xff";
        $logger = $this->createMock(StdoutLoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with('Annotation scan skipped 1 class.', [
            'event'             => 'annotation_scan.skipped_summary',
            'skipped_count'     => 1,
            'diagnostic_source' => 'annotation_cache',
        ]);
        $logger->expects(self::once())->method('info')->with(
            'Annotation scan skipped Missing\\Subject: Failure\\Name\\x00: a\\r\\n\\t\\x1B\\x00\\x7F' . "\xff",
            [
                'event'             => 'annotation_scan.skipped_class',
                'diagnostic_source' => 'annotation_cache',
                'sequence'          => 1,
                'package'           => 'acme/application',
                'class'             => 'Missing\\Subject',
                'exception_type'    => "Failure\\Name\x00",
                'exception_message' => $raw,
            ],
        );
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with(StdoutLoggerInterface::class)->willReturn($logger);
        $method = new ReflectionMethod(ContainerProvider::class, 'reportDiagnostics');
        self::assertTrue($method->invoke(null, $container, [
            [
            'sequence'       => 1,
            'package'        => 'acme/application',
            'class'          => 'Missing\\Subject',
            'exception_type' => "Failure\\Name\x00",
            'message'        => $raw,
        ],
        ]));
    }
}
