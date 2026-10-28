<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Integration;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SuperKernel\ScanIsolation\Processor\ExecScanProcessor;
use SuperKernel\ScanIsolation\Processor\PcntlScanProcessor;
use SuperKernel\ScanIsolation\Processor\ProcessScanProcessor;
use Throwable;

use function array_keys;
use function array_pop;
use function array_slice;
use function array_values;
use function copy;
use function dirname;
use function explode;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function implode;
use function in_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function mkdir;
use function php_ini_scanned_files;
use function proc_close;
use function proc_open;
use function rmdir;
use function str_replace;
use function stream_get_contents;
use function substr_count;
use function symlink;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;
use function var_export;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

final class BootstrapTest extends TestCase
{
    /**
     * @param class-string $processorClass
     *
     * @throws Throwable When fixture setup or the operation under test throws
     */
    #[DataProvider('processors')]
    public function testPublishesInIsolationAndRestoresSharedServicesWithoutRescanning(
        string $disabledFunctions,
        string $processorClass,
    ): void
    {
        if (!new $processorClass()->support()) {
            self::markTestSkipped($processorClass . ' is unavailable.');
        }

        $result = $this->runBootstrap($disabledFunctions, <<<'PHP'
            $first = new SuperKernel\Di\Provider\ContainerProvider()();
            $processor = $first->get(SuperKernel\ScanIsolation\Contract\ScanProcessorInterface::class);
            $bootstrap = new ReflectionMethod(SuperKernel\Di\Provider\ContainerProvider::class, 'bootstrap');
            $container = $bootstrap->invoke(
                null,
                new SuperKernel\ScanIsolation\Execution\Scanned(true),
                $processor,
            );
            $declaredBeforeGet = class_exists(BootstrapFixture\Service::class, false);
            rename(__DIR__ . '/composer.json', __DIR__ . '/composer.json.saved');
            rename(__DIR__ . '/composer.lock', __DIR__ . '/composer.lock.saved');
            $service = $container->get(BootstrapFixture\Service::class);
            $packages = $container->get(SuperKernel\Contract\PackageManagerInterface::class);
            $collector = $container->get(SuperKernel\Contract\AnnotationCollectorInterface::class);
            $aliases = true;
            foreach ([
                SuperKernel\Contract\AnnotationCollectorInterface::class,
                SuperKernel\Contract\BasePathInterface::class,
                SuperKernel\Composer\Contract\ComposerMetadataInterface::class,
                SuperKernel\Composer\Contract\PackageLocatorInterface::class,
                SuperKernel\Composer\Contract\PackageParserInterface::class,
                SuperKernel\Composer\Contract\PackageReconcilerInterface::class,
                SuperKernel\Contract\PackageManagerInterface::class,
                SuperKernel\Contract\ReflectorInterface::class,
                SuperKernel\Contract\VendorDirInterface::class,
                SuperKernel\ScanIsolation\Contract\ScanProcessorInterface::class,
                SuperKernel\Tokenizer\Contract\ClassNameParserInterface::class,
            ] as $id) {
                $entry = $container->get($id);
                $aliases = $aliases && $entry === $container->get($entry::class);
            }
            $vendorDir = $container->get(SuperKernel\Contract\VendorDirInterface::class);
            $cached = is_file($vendorDir . '/.cache/packages.ser');
            foreach ($packages->all() as $package) {
                $cached = $cached && is_file($vendorDir . '/.cache/classes/' . $package->name() . '.ser')
                    && is_file($vendorDir . '/.cache/annotations/' . $package->name() . '.ser');
            }
            file_put_contents(__DIR__ . "/result.json", json_encode([
                'processor' => $processor::class,
                'freshRoot' => $first !== $container,
                'declaredBeforeGet' => $declaredBeforeGet,
                'classmap' => $packages->classes('bootstrap/application')[BootstrapFixture\Service::class],
                'scans' => file(__DIR__ . '/scans', FILE_IGNORE_NEW_LINES),
                'constructions' => file(__DIR__ . '/services', FILE_IGNORE_NEW_LINES),
                'pid' => (string) getmypid(),
                'retainedService' => $service === $container->get(BootstrapFixture\Service::class),
                'currentContainer' => $service->container === $container,
                'sharedMetadata' => $service->metadata === $container->get(
                    SuperKernel\Composer\Contract\ComposerMetadataInterface::class,
                ),
                'sharedParser' => $service->parser === $container->get(
                    SuperKernel\Composer\Contract\PackageParserInterface::class,
                ),
                'sharedLocator' => $service->locator === $container->get(
                    SuperKernel\Composer\Contract\PackageLocatorInterface::class,
                ),
                'sharedReconciler' => $service->reconciler === $container->get(
                    SuperKernel\Composer\Contract\PackageReconcilerInterface::class,
                ),
                'sharedClassNameParser' => $service->classNameParser === $container->get(
                    SuperKernel\Tokenizer\Contract\ClassNameParserInterface::class,
                ),
                'parsedClassNames' => $service->classNameParser->parse(
                    (string) file_get_contents(__DIR__ . '/src/Service.php'),
                ),
                'aliases' => $aliases,
                'facadeReflector' => (new ReflectionProperty(
                    SuperKernel\Reflector\Facade\ReflectorFacade::class,
                    'reflector',
                ))->getValue() === $container->get(SuperKernel\Contract\ReflectorInterface::class),
                'cached' => $cached,
                'runtimeCreated' => is_dir($vendorDir . '/.runtime'),
                'packageCount' => count($packages->all()),
                'annotationPackageCount' => count($collector->getPackages()),
                'probeAttributes' => count($collector->getClassesWithAttribute(BootstrapFixture\Probe::class)),
            ], JSON_THROW_ON_ERROR));
            PHP);

        self::assertSame($processorClass, $result['processor']);
        self::assertTrue($result['freshRoot']);
        self::assertFalse($result['declaredBeforeGet']);
        self::assertStringEndsWith('/src/Service.php', $result['classmap']);
        self::assertCount(1, $result['scans']);
        self::assertNotSame($result['pid'], $result['scans'][0]);
        self::assertSame([
            $result['pid'],
        ], $result['constructions']);
        self::assertTrue($result['retainedService']);
        self::assertTrue($result['currentContainer']);
        self::assertTrue($result['sharedMetadata']);
        self::assertTrue($result['sharedParser']);
        self::assertTrue($result['sharedLocator']);
        self::assertTrue($result['sharedReconciler']);
        self::assertTrue($result['sharedClassNameParser']);
        self::assertSame([
            'BootstrapFixture\\Service',
        ], $result['parsedClassNames']);
        self::assertTrue($result['aliases']);
        self::assertTrue($result['facadeReflector']);
        self::assertTrue($result['cached']);
        self::assertFalse($result['runtimeCreated']);
        self::assertSame($result['packageCount'], $result['annotationPackageCount']);
        self::assertSame(1, $result['probeAttributes']);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRestorePhaseRejectsMissingCachesWithoutScanning(): void
    {
        $result = $this->runBootstrap('', <<<'PHP'
            try {
                $bootstrap = new ReflectionMethod(SuperKernel\Di\Provider\ContainerProvider::class, 'bootstrap');
                $bootstrap->invoke(
                    null,
                    new SuperKernel\ScanIsolation\Execution\Scanned(true),
                    new SuperKernel\ScanIsolation\Provider\ScanProcessorProvider()(),
                );
                throw new LogicException('Missing caches must be rejected.');
            } catch (Throwable $failure) {
                while (null !== $failure->getPrevious()) {
                    $failure = $failure->getPrevious();
                }
                file_put_contents(__DIR__ . "/result.json", json_encode([
                    'failure' => $failure::class,
                    'scanned' => is_file(__DIR__ . '/scans'),
                    'published' => is_file(__DIR__ . '/vendor/.cache/packages.ser'),
                ], JSON_THROW_ON_ERROR));
            }
            PHP);

        self::assertSame('SuperKernel\Composer\Exception\PackageCacheException', $result['failure']);
        self::assertFalse($result['scanned']);
        self::assertFalse($result['published']);
    }

    /**
     * @param class-string $processorClass
     *
     * @throws Throwable When fixture execution fails
     */
    #[DataProvider('diagnosticScenarios')]
    public function testReportsInTheScanningProcessWithTheSelectedLogger(
        string $disabledFunctions,
        string $processorClass,
        int    $skipped,
        string $mode,
    ): void
    {
        if (!new $processorClass()->support()) {
            self::markTestSkipped($processorClass . ' is unavailable.');
        }
        $result = $this->diagnosticBootstrap($disabledFunctions, $skipped, $mode);
        self::assertSame($processorClass, $result['processor']);
        self::assertCount($skipped, $result['diagnostics']);
        self::assertFalse($result['loggerLoaded']);
        self::assertFalse($result['skippedLoaded']);
        self::assertTrue($result['suppliedIdentity']);
        $custom = !in_array($mode, [
            'default',
            'missing',
        ], true);
        self::assertCount($custom && 0 < $skipped ? 1 : 0, $result['loggers']);
        foreach ($result['loggers'] as $logger) {
            self::assertNotSame($result['pid'], $logger['pid']);
            self::assertSame($skipped, $logger['diagnostics']);
        }
        $combined = $result['stdout'] . $result['stderr'];
        if ('missing' === $mode) {
            self::assertSame('', $result['stdout']);
            self::assertSame($skipped, substr_count($result['stderr'], "\033[33mAnnotation scan warning:"));
            return;
        }
        $visible = 0 < $skipped && 'error' !== $mode;
        self::assertSame($visible ? 1 : 0, substr_count($combined, '[WARNING]'));
        self::assertSame($visible && 'warning' !== $mode ? $skipped : 0, substr_count($combined, '[INFO]'));
        self::assertStringNotContainsString('Annotation scan warning:', $combined);
        if ($visible) {
            self::assertStringContainsString(
                1 === $skipped ? 'Annotation scan skipped 1 class.' : 'Annotation scan skipped 3 classes.',
                $combined,
            );
            $stderr = ExecScanProcessor::class === $processorClass || 'stderr' === $mode;
            self::assertSame('', $result[$stderr ? 'stdout' : 'stderr']);
        }
        if ($custom && 0 < $skipped) {
            self::assertCount(1 + $skipped, $result['records']);
            self::assertSame('warning', $result['records'][0]['level']);
            self::assertSame($skipped, $result['records'][0]['context']['skipped_count']);
            foreach (array_slice($result['records'], 1) as $index => $record) {
                self::assertSame('info', $record['level']);
                self::assertSame($index + 1, $record['context']['sequence']);
                self::assertSame('annotation_cache', $record['context']['diagnostic_source']);
                self::assertSame('bootstrap/application', $record['context']['package']);
                self::assertStringContainsString($record['context']['class'], $record['message']);
                self::assertStringContainsString($record['context']['exception_message'], $record['message']);
                self::assertNotSame($result['pid'], $record['pid']);
            }
        }
    }

    /** @return iterable<string, array{string, class-string, int, string}> */
    public static function diagnosticScenarios(): iterable
    {
        foreach (self::processors() as $name => [$disabled, $processor]) {
            foreach ([
                0,
                1,
                3,
            ] as $count) {
                foreach ([
                    'default',
                    'info',
                    'warning',
                    'error',
                    'stderr',
                    'missing',
                ] as $mode) {
                    yield $name . '-' . $count . '-' . $mode => [
                        $disabled,
                        $processor,
                        $count,
                        $mode,
                    ];
                }
            }
        }
    }

    /**
     * @param class-string $processorClass
     *
     * @throws Throwable When fixture execution fails
     */
    #[DataProvider('loggingFailures')]
    public function testFallsBackForAllDiagnosticsAndRejectsFailedChildCompletion(
        string $disabledFunctions,
        string $processorClass,
        string $mode,
    ): void
    {
        if (!new $processorClass()->support()) {
            self::markTestSkipped($processorClass . ' is unavailable.');
        }
        $result = $this->diagnosticBootstrap($disabledFunctions, 3, $mode);
        self::assertSame($processorClass, $result['processor']);
        self::assertFalse($result['completed']);
        self::assertGreaterThan(0, $result['failureCode']);
        self::assertCount(1, $result['loggers']);
        self::assertNotSame($result['pid'], $result['loggers'][0]['pid']);
        self::assertSame(3, substr_count($result['stderr'], "\033[33mAnnotation scan warning:"));
        self::assertStringContainsString(match ($mode) {
            'construct-failure'  => 'logger construction failed',
            'dependency-failure' => 'missing.logger.dependency',
            default              => 'logger write failed',
        }, $result['stderr'] . $result['stdout']);
        self::assertFalse($result['loggerLoaded']);
    }

    /** @return iterable<string, array{string, class-string, string}> */
    public static function loggingFailures(): iterable
    {
        foreach (self::processors() as $name => [$disabled, $processor]) {
            foreach ([
                'construct-failure',
                'dependency-failure',
                'write-1',
                'write-2',
                'write-3',
                'write-4',
            ] as $mode) {
                yield $name . '-' . $mode => [
                    $disabled,
                    $processor,
                    $mode,
                ];
            }
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable When fixture execution fails
     */
    private function diagnosticBootstrap(string $disabled, int $skipped, string $mode): array
    {
        return $this->runBootstrap($disabled, <<<'PHP'
            $processor = new SuperKernel\ScanIsolation\Provider\ScanProcessorProvider()();
            $container = null;
            $failureCode = null;
            try {
                $container = new SuperKernel\Di\Provider\ContainerProvider()();
            } catch (SuperKernel\ScanIsolation\Exception\ScanProcessFailedException $failure) {
                $failureCode = $failure->exitCode();
            }
            $collector = $container?->get(SuperKernel\Contract\AnnotationCollectorInterface::class);
            $decode = static fn(string $path): array => is_file($path)
                ? array_map(static fn(string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
                    file($path, FILE_IGNORE_NEW_LINES)) : [];
            file_put_contents(__DIR__ . '/result.json', json_encode([
                'pid' => getmypid(),
                'processor' => $processor::class,
                'completed' => null !== $container,
                'failureCode' => $failureCode,
                'diagnostics' => $collector?->getDiagnostics(),
                'loggerLoaded' => class_exists(BootstrapFixture\ProbeLogger::class, false),
                'skippedLoaded' => class_exists(BootstrapFixture\Skipped1::class, false),
                'suppliedIdentity' => null !== $container && $container === new SuperKernel\Di\Provider\ContainerProvider()($container),
                'loggers' => $decode(__DIR__ . '/loggers'),
                'records' => $decode(__DIR__ . '/records'),
            ], JSON_THROW_ON_ERROR));
            PHP, $skipped, $mode);
    }

    /** @throws Throwable When fixture execution or archive creation fails */
    public function testPharRestoresAndReportsEveryTimeInTheCurrentProcessWithoutScanning(): void
    {
        $result = $this->runBootstrap('', <<<'PHP'
            new SuperKernel\Di\Provider\ContainerProvider()();
            $archive = new Phar(__DIR__ . '/probe.phar');
            $archive['composer.json'] = file_get_contents(__DIR__ . '/composer.json');
            $archive['composer.lock'] = file_get_contents(__DIR__ . '/composer.lock');
            $prefixes = [];
            foreach ($loader->getPrefixesPsr4() as $prefix => $paths) {
                if (!str_starts_with($prefix, 'SuperKernel\\') || str_contains($prefix, '\\Tests\\')) {
                    continue;
                }
                $relative = 'components/' . str_replace('\\', '/', $prefix);
                $prefixes[$prefix] = $relative;
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($paths[0],
                    FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file->getExtension() === 'php') {
                        $archive->addFile(
                            $file->getPathname(),
                            $relative . substr($file->getPathname(), strlen($paths[0]) + 1),
                        );
                    }
                }
            }
            // Use the installed autoloader for external standards and a leading archive loader for framework source.
            $loaderFile = new ReflectionClass(Composer\Autoload\ClassLoader::class)->getFileName();
            $archive['probe.php'] = "<?php\nrequire "
                . var_export(dirname($loaderFile, 2) . '/autoload.php', true) . ";\n"
                . '$prefixes = ' . var_export($prefixes, true) . ";\n"
                . '$root = ' . var_export(__DIR__, true) . ";\n"
                . <<<'PROBE'
                spl_autoload_register(static function (string $class) use ($prefixes): void {
                    foreach ($prefixes as $prefix => $path) {
                        if (str_starts_with($class, $prefix)) {
                            $file = __DIR__ . '/' . $path
                                . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                            if (is_file($file)) { require $file; }
                            return;
                        }
                    }
                }, true, true);
                spl_autoload_register(static function (string $class) use ($root): void {
                    if (str_starts_with($class, 'BootstrapFixture\\')) {
                        require $root . '/src/' . substr($class, strlen('BootstrapFixture\\')) . '.php';
                    }
                });
                $processors = [];
                for ($index = 0; $index < 2; ++$index) {
                    $container = new SuperKernel\Di\Provider\ContainerProvider()();
                    $processor = $container->get(SuperKernel\ScanIsolation\Contract\ScanProcessorInterface::class);
                    $processors[] = $processor::class;
                }
                $oldPath = $root . '/vendor/.cache/annotations/bootstrap/application.ser';
                file_put_contents($oldPath, str_replace('schemaVersion";i:4;', 'schemaVersion";i:3;',
                    file_get_contents($oldPath)));
                $oldCacheRejected = false;
                try {
                    new SuperKernel\Di\Provider\ContainerProvider()();
                } catch (SuperKernel\Annotator\Exception\AnnotationCacheException) {
                    $oldCacheRejected = true;
                }
                file_put_contents($root . '/result.json', json_encode([
                    'oldCacheRejected' => $oldCacheRejected,
                    'processors' => $processors,
                    'pid' => getmypid(),
                    'scans' => file($root . '/scans', FILE_IGNORE_NEW_LINES),
                    'loggers' => array_map(static fn(string $line): array => json_decode($line, true),
                        file($root . '/loggers', FILE_IGNORE_NEW_LINES)),
                ], JSON_THROW_ON_ERROR));
                PROBE;
            $archive->setStub(Phar::createDefaultStub('probe.php'));
            unset($archive);
            passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/probe.phar'), $exit);
            if (0 !== $exit) { throw new RuntimeException('Phar probe failed.'); }
            PHP, 1, 'info');
        self::assertSame([
            'SuperKernel\ScanIsolation\Processor\NullScanProcessor',
            'SuperKernel\ScanIsolation\Processor\NullScanProcessor',
        ], $result['processors']);
        self::assertTrue($result['oldCacheRejected']);
        self::assertCount(1, $result['scans']);
        self::assertCount(3, $result['loggers']);
        self::assertNotSame($result['pid'], $result['loggers'][0]['pid']);
        self::assertSame($result['pid'], $result['loggers'][1]['pid']);
        self::assertSame($result['pid'], $result['loggers'][2]['pid']);
        self::assertSame(3, substr_count($result['stdout'], '[WARNING]'));
        self::assertSame(3, substr_count($result['stdout'], '[INFO]'));
        self::assertSame('', $result['stderr']);
    }

    /** @throws Throwable When fixture execution fails */
    public function testBrokenEmergencyStreamDoesNotReplaceTheOriginalLoggerFailure(): void
    {
        $result = $this->runBootstrap('', <<<'PHP'
            try {
                new SuperKernel\Di\Provider\ContainerProvider()();
            } catch (SuperKernel\ScanIsolation\Exception\ScanProcessFailedException $failure) {
                file_put_contents(__DIR__ . '/result.json', json_encode([
                    'failureCode' => $failure->exitCode(),
                    'original' => json_decode(file_get_contents(__DIR__ . '/failure.json'), true),
                ], JSON_THROW_ON_ERROR));
                return;
            } catch (Throwable $exception) {
                file_put_contents(__DIR__ . '/failure.json', json_encode([
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                ], JSON_THROW_ON_ERROR));
                exit(23);
            }
            file_put_contents(__DIR__ . '/result.json', file_get_contents(__DIR__ . '/failure.json'));
            PHP, 1, 'closed-stderr');
        self::assertSame(23, $result['failureCode']);
        self::assertSame('RuntimeException', $result['original']['type']);
        self::assertSame('original logger failure with closed stderr', $result['original']['message']);
        self::assertSame('', $result['stdout']);
        self::assertSame('', $result['stderr']);
    }

    /** @throws Throwable When fixture execution fails */
    public function testAbsentLoggerWithBrokenStderrRaisesAnOutputFailureInTheChild(): void
    {
        $result = $this->runBootstrap('', <<<'PHP'
            fclose(STDERR);
            try {
                new SuperKernel\Di\Provider\ContainerProvider()();
            } catch (SuperKernel\ScanIsolation\Exception\ScanProcessFailedException $failure) {
                file_put_contents(__DIR__ . '/result.json', json_encode([
                    'failureCode' => $failure->exitCode(),
                    'original' => json_decode(file_get_contents(__DIR__ . '/failure.json'), true),
                ], JSON_THROW_ON_ERROR));
                return;
            } catch (Throwable $exception) {
                file_put_contents(__DIR__ . '/failure.json', json_encode([
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                ], JSON_THROW_ON_ERROR));
                exit(23);
            }
            file_put_contents(__DIR__ . '/result.json', file_get_contents(__DIR__ . '/failure.json'));
            PHP, 1, 'missing');
        self::assertSame(23, $result['failureCode']);
        self::assertSame('TypeError', $result['original']['type']);
        self::assertStringContainsString('valid stream resource', $result['original']['message']);
    }

    /**
     * @param class-string $processorClass
     *
     * @throws Throwable When the compound cache failure fixture fails
     */
    #[DataProvider('processors')]
    public function testFailedPublicationAndInvalidationNeverRestoreTheStillValidOldCache(
        string $disabledFunctions,
        string $processorClass,
    ): void
    {
        if (!new $processorClass()->support()) {
            self::markTestSkipped($processorClass . ' is unavailable.');
        }
        $result = $this->runBootstrap($disabledFunctions, <<<'PHP'
            $first = new SuperKernel\Di\Provider\ContainerProvider()();
            $processor = $first->get(SuperKernel\ScanIsolation\Contract\ScanProcessorInterface::class);
            $cacheDirectory = __DIR__ . '/vendor/.cache';
            $index = $cacheDirectory . '/packages.ser';
            $before = file_get_contents($index);
            chmod($cacheDirectory, 0555);
            clearstatcache();
            if (is_writable($cacheDirectory)) {
                chmod($cacheDirectory, 0755);
                file_put_contents(__DIR__ . '/result.json', json_encode(['permissionsEnforced' => false]));
                return;
            }
            $cache = new SuperKernel\Annotator\Cache\AnnotationCache(
                $first->get(SuperKernel\Contract\VendorDirInterface::class),
            );
            $invalidationFailed = false;
            try {
                $cache->invalidate();
            } catch (SuperKernel\Annotator\Exception\AnnotationCacheException) {
                $invalidationFailed = true;
            }
            $completed = false;
            $code = null;
            try {
                new SuperKernel\Di\Provider\ContainerProvider()();
                $completed = true;
            } catch (SuperKernel\ScanIsolation\Exception\ScanProcessFailedException $failure) {
                $code = $failure->exitCode();
            } finally {
                chmod($cacheDirectory, 0755);
            }
            $restored = $cache->read($first->get(SuperKernel\Contract\PackageManagerInterface::class));
            file_put_contents(__DIR__ . '/result.json', json_encode([
                'permissionsEnforced' => true, 'processor' => $processor::class,
                'completed' => $completed, 'failureCode' => $code, 'invalidationFailed' => $invalidationFailed,
                'sameIndex' => $before === file_get_contents($index),
                'oldCacheReadable' => [] !== $restored->getPackages(),
            ], JSON_THROW_ON_ERROR));
            PHP, 1, 'missing');
        if (!$result['permissionsEnforced']) {
            self::markTestSkipped('The current user bypasses directory write permissions.');
        }
        self::assertSame($processorClass, $result['processor']);
        self::assertFalse($result['completed']);
        self::assertGreaterThan(0, $result['failureCode']);
        self::assertTrue($result['sameIndex']);
        self::assertTrue($result['invalidationFailed']);
        self::assertTrue($result['oldCacheReadable']);
        self::assertStringContainsString('Unable to publish the annotation cache file', $result['stderr']);
    }

    /** @return iterable<string, array{string, class-string}> */
    public static function processors(): iterable
    {
        yield 'pcntl' => [
            '',
            PcntlScanProcessor::class,
        ];
        yield 'process' => [
            'pcntl_fork',
            ProcessScanProcessor::class,
        ];
        yield 'exec' => [
            'pcntl_fork,proc_open',
            ExecScanProcessor::class,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable When fixture setup or a supplied test callback fails
     */
    private function runBootstrap(
        string $disabledFunctions,
        string $body,
        ?int   $skipped = null,
        string $loggerMode = 'default',
    ): array
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

        self::assertFileExists($autoload);
        $vendor = dirname($autoload);
        $root = tempnam(sys_get_temp_dir(), 'super-kernel-bootstrap-');
        self::assertIsString($root);
        unlink($root);
        mkdir($root);

        try {
            mkdir($root . '/vendor');
            mkdir($root . '/src');
            mkdir($root . '/ini');
            copy(dirname($vendor) . '/composer.lock', $root . '/composer.lock');
            $lock = json_decode(file_get_contents($root . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);

            // The component is the root package, so its own lock does not list it.
            $component = json_decode(
                file_get_contents(dirname($vendor) . '/composer.json'),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $lock['packages'][] = [
                'name'     => $component['name'],
                'version'  => 'dev-main',
                'type'     => $component['type'],
                'require'  => $component['require'],
                'autoload' => $component['autoload'],
            ];
            file_put_contents($root . '/composer.lock', json_encode($lock, JSON_THROW_ON_ERROR));
            $link = $root . '/vendor/' . $component['name'];
            mkdir(dirname($link), recursive: true);
            symlink(dirname($vendor), $link);

            if (null !== $skipped) {
                $available = [];
                foreach ([
                    ...$lock['packages'],
                    ...$lock['packages-dev'],
                ] as $package) {
                    $available[$package['name']] = $package;
                }
                $selected = [];
                $pending = [
                    'super-kernel/di',
                ];
                if ('missing' !== $loggerMode) {
                    $pending[] = 'super-kernel/logger';
                }
                while ([] !== $pending) {
                    $name = array_pop($pending);
                    if (isset($selected[$name]) || !isset($available[$name])) {
                        continue;
                    }
                    $selected[$name] = $available[$name];
                    foreach (array_keys($available[$name]['require'] ?? []) as $dependency) {
                        $pending[] = $dependency;
                    }
                }
                $lock['packages'] = array_values($selected);
                $lock['packages-dev'] = [];
                file_put_contents($root . '/composer.lock', json_encode($lock, JSON_THROW_ON_ERROR));
            }

            foreach ([
                ...$lock['packages'],
                ...$lock['packages-dev'],
            ] as $package) {
                $target = $vendor . '/' . $package['name'];

                if (!is_dir($target)) {
                    continue;
                }

                $link = $root . '/vendor/' . $package['name'];

                if (!is_dir(dirname($link))) {
                    mkdir(dirname($link), recursive: true);
                }

                symlink($target, $link);
            }

            if (!is_dir($root . '/vendor/composer')) {
                mkdir($root . '/vendor/composer');
            }

            copy($vendor . '/composer/installed.php', $root . '/vendor/composer/installed.php');

            file_put_contents($root . '/composer.json', json_encode([
                'name'   => 'bootstrap/application',
                'config' => [
                    'vendor-dir' => $root . '/vendor',
                ],
                'autoload' => [
                    'psr-4' => [
                        'BootstrapFixture\\' => 'src/',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));
            file_put_contents($root . '/src/Probe.php', <<<'PHP'
                <?php
                namespace BootstrapFixture;
                #[\Attribute(\Attribute::TARGET_CLASS)]
                final class Probe
                {
                    public function __construct()
                    {
                        file_put_contents(__DIR__ . '/../scans', getmypid() . "\n", FILE_APPEND);
                    }
                }
                PHP);
            file_put_contents($root . '/src/Service.php', <<<'PHP'
                <?php
                namespace BootstrapFixture;
                #[Probe]
                final readonly class Service
                {
                    public function __construct(
                        public \Psr\Container\ContainerInterface $container,
                        public \SuperKernel\Composer\Contract\ComposerMetadataInterface $metadata,
                        public \SuperKernel\Composer\Contract\PackageParserInterface $parser,
                        public \SuperKernel\Composer\Contract\PackageLocatorInterface $locator,
                        public \SuperKernel\Composer\Contract\PackageReconcilerInterface $reconciler,
                        public \SuperKernel\Tokenizer\Contract\ClassNameParserInterface $classNameParser,
                    ) {
                        file_put_contents(__DIR__ . '/../services', getmypid() . "\n", FILE_APPEND);
                    }
                }
                PHP);
            if (null !== $skipped) {
                for ($index = 1; $index <= $skipped; ++$index) {
                    file_put_contents(
                        $root . '/src/Skipped' . $index . '.php',
                        "<?php\nnamespace BootstrapFixture;\n#[MissingAttribute] final class Skipped$index {}\n",
                    );
                }
                if (!in_array($loggerMode, [
                    'default',
                    'missing',
                ], true)) {
                    file_put_contents($root . '/src/ProbeLogger.php', str_replace('__MODE__', $loggerMode, <<<'PHP'
                        <?php
                        namespace BootstrapFixture;
                        #[\SuperKernel\Attribute\Provider(\SuperKernel\Contract\StdoutLoggerInterface::class)]
                        final class ProbeLogger implements \SuperKernel\Contract\StdoutLoggerInterface
                        {
                            use \Psr\Log\LoggerTrait;
                            private \Psr\Log\LoggerInterface $logger;
                            private int $calls = 0;
                            public function __construct(
                                \SuperKernel\Logger\Factory\LoggerFactory $factory,
                                \Psr\Container\ContainerInterface $container,
                            ) {
                                file_put_contents(__DIR__ . '/../loggers', json_encode([
                                    'pid' => getmypid(),
                                    'diagnostics' => count($container->get(
                                        \SuperKernel\Contract\AnnotationCollectorInterface::class,
                                    )->getDiagnostics()),
                                ]) . "\n", FILE_APPEND);
                                if ('construct-failure' === '__MODE__') {
                                    throw new \RuntimeException('logger construction failed');
                                }
                                if ('dependency-failure' === '__MODE__') {
                                    $container->get('missing.logger.dependency');
                                }
                                $this->logger = $factory->create(
                                    'probe',
                                    'stderr' === '__MODE__' ? 'php://stderr' : 'php://stdout',
                                    in_array('__MODE__', ['warning', 'error'], true) ? '__MODE__' : 'info',
                                    new \SuperKernel\Logger\Formatter\ConsoleFormatter(),
                                );
                            }
                            public function log(mixed $level, string|\Stringable $message, array $context = []): void
                            {
                                ++$this->calls;
                                file_put_contents(__DIR__ . '/../records', json_encode([
                                    'pid' => getmypid(), 'level' => $level, 'message' => (string) $message,
                                    'context' => $context,
                                ]) . "\n", FILE_APPEND);
                                if ('closed-stderr' === '__MODE__') {
                                    fclose(STDERR);
                                    throw new \RuntimeException('original logger failure with closed stderr');
                                }
                                if ('write-' . $this->calls === '__MODE__') {
                                    throw new \RuntimeException('logger write failed');
                                }
                                $this->logger->log($level, $message, $context);
                            }
                        }
                        PHP));
                }
            }

            file_put_contents(
                $root . '/bootstrap.php',
                "<?php\ndeclare(strict_types=1);\n\$loader = require " . var_export($autoload, true) . ";\n"
                . "\$loader->setClassMapAuthoritative(false);\n"
                . "\$loader->addPsr4('BootstrapFixture\\\\', __DIR__ . '/src/');\n" . $body,
            );
            file_put_contents($root . '/ini/99-scan.ini', 'disable_functions=' . $disabledFunctions . "\n");
            $directories = [];

            foreach (explode(',', php_ini_scanned_files() ?: '') as $file) {
                if ('' !== trim($file)) {
                    $directories[dirname(trim($file))] = true;
                }
            }

            $directories[$root . '/ini'] = true;
            $environment = getenv();
            $environment['PHP_INI_SCAN_DIR'] = implode(':', array_keys($directories));
            $process = proc_open([
                PHP_BINARY,
                '-d',
                'phar.readonly=0',
                $root . '/bootstrap.php',
            ], [
                0 => [
                    'pipe',
                    'r',
                ],
                1 => [
                    'pipe',
                    'w',
                ],
                2 => [
                    'pipe',
                    'w',
                ],
            ], $pipes, $root, $environment);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error . $output);

            return json_decode(file_get_contents($root . '/result.json'), true, 512, JSON_THROW_ON_ERROR) + [
                'stdout' => $output,
                'stderr' => $error,
            ];
        } finally {
            foreach (new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            ) as $file) {
                if ($file->isDir() && !$file->isLink()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }

            rmdir($root);
        }
    }
}
