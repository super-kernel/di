<?php

declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use LogicException;
use Psr;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionException;
use SuperKernel;
use SuperKernel\Annotator\AnnotationCollector;
use SuperKernel\Annotator\Diagnostic\DiagnosticOutput;
use SuperKernel\Annotator\Model\PackageAnnotations;
use SuperKernel\Annotator\Provider\AnnotationCollectorProvider;
use SuperKernel\Annotator\Scan\ClassAnnotationScanner;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Composer\Contract\ComposerMetadataInterface;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\BasePathInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Contract\ScanDiagnosticsInterface;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\Contract\VendorDirInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Exception\NotFoundException;
use SuperKernel\Reflector;
use SuperKernel\Reflector\Provider\ReflectorProvider;
use SuperKernel\ScanIsolation\Contract\ScannedInterface;
use SuperKernel\ScanIsolation\Contract\ScanProcessorInterface;
use SuperKernel\ScanIsolation\Processor\NullScanProcessor;
use SuperKernel\ScanIsolation\Processor\PhaseScanProcessor;
use SuperKernel\ScanIsolation\Provider\ScanProcessorProvider;
use Throwable;

use function count;
use function ord;
use function preg_replace_callback;
use function sprintf;

#[
    FactoryProvider(ContainerInterface::class),
    FactoryProvider(PsrContainerInterface::class),
]
final readonly class ContainerProvider
{
    /**
     * Named production classes, interfaces, traits, and enums in DI and its transitive runtime dependencies.
     *
     * Internal declarations are intentionally included as class names for bootstrap reflection.
     * Refresh this list when those packages change their production declarations or runtime requirements.
     *
     * @var list<class-string>
     *
     * @noinspection PhpInternalEntityUsedInspection
     */
    private const array CLASS_NAMES = [
        // psr/container
        Psr\Container\ContainerExceptionInterface::class,
        Psr\Container\ContainerInterface::class,
        Psr\Container\NotFoundExceptionInterface::class,

        // psr/log
        Psr\Log\AbstractLogger::class,
        Psr\Log\InvalidArgumentException::class,
        Psr\Log\LogLevel::class,
        Psr\Log\LoggerAwareInterface::class,
        Psr\Log\LoggerAwareTrait::class,
        Psr\Log\LoggerInterface::class,
        Psr\Log\LoggerTrait::class,
        Psr\Log\NullLogger::class,

        // super-kernel/annotator
        SuperKernel\Annotator\AnnotationCollector::class,
        SuperKernel\Annotator\Cache\AnnotationCache::class,
        SuperKernel\Annotator\Cache\SerializedPackageAnnotations::class,
        SuperKernel\Annotator\Cache\SerializedPackageReferences::class,
        SuperKernel\Annotator\Diagnostic\DiagnosticOutput::class,
        SuperKernel\Annotator\Exception\AnnotationCacheException::class,
        SuperKernel\Annotator\Exception\AnnotationCollectorExceptionInterface::class,
        SuperKernel\Annotator\Exception\AnnotationScanException::class,
        SuperKernel\Annotator\Model\AttributeMetadata::class,
        SuperKernel\Annotator\Model\PackageAnnotations::class,
        SuperKernel\Annotator\Provider\AnnotationCollectorProvider::class,
        SuperKernel\Annotator\Scan\AnnotationScanSession::class,
        SuperKernel\Annotator\Scan\AnnotationScanWorker::class,
        SuperKernel\Annotator\Scan\AnnotationScanner::class,
        SuperKernel\Annotator\Scan\ClassAnnotationScanner::class,

        // super-kernel/attribute
        SuperKernel\Attribute\Autowired::class,
        SuperKernel\Attribute\Configuration::class,
        SuperKernel\Attribute\Definer::class,
        SuperKernel\Attribute\FactoryProvider::class,
        SuperKernel\Attribute\Listener::class,
        SuperKernel\Attribute\PropertyHandler::class,
        SuperKernel\Attribute\Provider::class,
        SuperKernel\Attribute\Provision::class,
        SuperKernel\Attribute\Resolver::class,

        // super-kernel/composer
        SuperKernel\Composer\Cache\PackageReconciler::class,
        SuperKernel\Composer\ClassMap\ClassMapBuilder::class,
        SuperKernel\Composer\Contract\ComposerAutoloadInterface::class,
        SuperKernel\Composer\Contract\ComposerJsonInterface::class,
        SuperKernel\Composer\Contract\ComposerLockInterface::class,
        SuperKernel\Composer\Contract\ComposerMetadataInterface::class,
        SuperKernel\Composer\Contract\ComposerPackageInterface::class,
        SuperKernel\Composer\Contract\DependencyInterface::class,
        SuperKernel\Composer\Contract\DetectorInterface::class,
        SuperKernel\Composer\Contract\PackageInterface::class,
        SuperKernel\Composer\Contract\PackageLocatorInterface::class,
        SuperKernel\Composer\Contract\PackageParserInterface::class,
        SuperKernel\Composer\Contract\PackageReconcilerInterface::class,
        SuperKernel\Composer\Contract\ScanRuleInterface::class,
        SuperKernel\Composer\Contract\ScanRuleSetInterface::class,
        SuperKernel\Composer\Detector\ComponentDetector::class,
        SuperKernel\Composer\Detector\ComposerScriptDetector::class,
        SuperKernel\Composer\Detector\NullDetector::class,
        SuperKernel\Composer\Detector\ScriptDetector::class,
        SuperKernel\Composer\Detector\WorkingDirectoryDetector::class,
        SuperKernel\Composer\Enum\AutoloadScope::class,
        SuperKernel\Composer\Enum\PackageType::class,
        SuperKernel\Composer\Enum\ScanRuleKind::class,
        SuperKernel\Composer\Enum\ScanRuleSource::class,
        SuperKernel\Composer\Exception\AutoloadRuleException::class,
        SuperKernel\Composer\Exception\ClassMapScanException::class,
        SuperKernel\Composer\Exception\ComposerFileException::class,
        SuperKernel\Composer\Exception\LockFileException::class,
        SuperKernel\Composer\Exception\ManifestException::class,
        SuperKernel\Composer\Exception\MetadataExceptionInterface::class,
        SuperKernel\Composer\Exception\PackageCacheException::class,
        SuperKernel\Composer\Exception\PackageLocationException::class,
        SuperKernel\Composer\Exception\PackageNotFoundException::class,
        SuperKernel\Composer\Exception\ProjectRootNotFoundException::class,
        SuperKernel\Composer\Locator\PackageLocator::class,
        SuperKernel\Composer\Manager\PackageManager::class,
        SuperKernel\Composer\Metadata\AutoloadRuleParser::class,
        SuperKernel\Composer\Metadata\ComposerAutoload::class,
        SuperKernel\Composer\Metadata\ComposerFileReader::class,
        SuperKernel\Composer\Metadata\ComposerJson::class,
        SuperKernel\Composer\Metadata\ComposerLock::class,
        SuperKernel\Composer\Metadata\ComposerMetadata::class,
        SuperKernel\Composer\Metadata\ComposerPackage::class,
        SuperKernel\Composer\Metadata\ComposerPackageName::class,
        SuperKernel\Composer\Metadata\PackageParser::class,
        SuperKernel\Composer\Metadata\RequirementProcessor::class,
        SuperKernel\Composer\Model\Dependency::class,
        SuperKernel\Composer\Model\EnvironmentRequirement::class,
        SuperKernel\Composer\Model\Package::class,
        SuperKernel\Composer\Model\ScanRule::class,
        SuperKernel\Composer\Model\ScanRuleSet::class,
        SuperKernel\Composer\Path\BasePath::class,
        SuperKernel\Composer\Path\FilesystemPath::class,
        SuperKernel\Composer\Path\VendorDir::class,
        SuperKernel\Composer\Provider\BasePathProvider::class,
        SuperKernel\Composer\Provider\PackageManagerProvider::class,
        SuperKernel\Composer\Provider\VendorDirProvider::class,

        // super-kernel/contract
        SuperKernel\Contract\AnnotationCollectorInterface::class,
        SuperKernel\Contract\ApplicationInterface::class,
        SuperKernel\Contract\AttributeMetadataInterface::class,
        SuperKernel\Contract\BasePathInterface::class,
        SuperKernel\Contract\ConfigInterface::class,
        SuperKernel\Contract\ContainerInterface::class,
        SuperKernel\Contract\EnvironmentRequirementInterface::class,
        SuperKernel\Contract\EventInterface::class,
        SuperKernel\Contract\ExceptionDispatcherInterface::class,
        SuperKernel\Contract\ListenerInterface::class,
        SuperKernel\Contract\PackageAnnotationsInterface::class,
        SuperKernel\Contract\PackageManagerInterface::class,
        SuperKernel\Contract\PackageMetadataInterface::class,
        SuperKernel\Contract\PropertyAttributeHandlerInterface::class,
        SuperKernel\Contract\PropertyContextInterface::class,
        SuperKernel\Contract\ReflectorInterface::class,
        SuperKernel\Contract\ScanDiagnosticsInterface::class,
        SuperKernel\Contract\StdoutLoggerInterface::class,
        SuperKernel\Contract\VendorDirInterface::class,

        // super-kernel/di
        SuperKernel\Di\Container::class,
        SuperKernel\Di\Contract\DefinerInterface::class,
        SuperKernel\Di\Contract\DefinitionFactoryInterface::class,
        SuperKernel\Di\Contract\DefinitionInterface::class,
        SuperKernel\Di\Contract\PropertyDispatcherInterface::class,
        SuperKernel\Di\Contract\ResolverFactoryInterface::class,
        SuperKernel\Di\Contract\ResolverInterface::class,
        SuperKernel\Di\Contract\RetainedEntriesInterface::class,
        SuperKernel\Di\Definer\ClassDefiner::class,
        SuperKernel\Di\Definer\FactoryDefiner::class,
        SuperKernel\Di\Definer\ProviderDefiner::class,
        SuperKernel\Di\Definition\ClassDefinition::class,
        SuperKernel\Di\Definition\FactoryDefinition::class,
        SuperKernel\Di\Definition\MethodDefinition::class,
        SuperKernel\Di\Definition\PropertyDefinition::class,
        SuperKernel\Di\Definition\ProviderDefinition::class,
        SuperKernel\Di\Exception\ContainerException::class,
        SuperKernel\Di\Exception\NotFoundException::class,
        SuperKernel\Di\Exception\ResolutionException::class,
        SuperKernel\Di\Factory\DefinitionFactory::class,
        SuperKernel\Di\Factory\ResolverFactory::class,
        SuperKernel\Di\Property\PropertyContext::class,
        SuperKernel\Di\Property\PropertyDispatcher::class,
        SuperKernel\Di\Storage\RetainedEntries::class,
        SuperKernel\Di\PropertyHandler\AutowiredPropertyHandler::class,
        SuperKernel\Di\Provider\ContainerProvider::class,
        SuperKernel\Di\Resolver\ClassResolver::class,
        SuperKernel\Di\Resolver\FactoryResolver::class,
        SuperKernel\Di\Resolver\MethodResolver::class,
        SuperKernel\Di\Resolver\PropertyResolver::class,
        SuperKernel\Di\Resolver\ProviderResolver::class,

        // super-kernel/reflector
        SuperKernel\Reflector\Facade\ReflectorFacade::class,
        SuperKernel\Reflector\Provider\ReflectorProvider::class,
        SuperKernel\Reflector\Reflector::class,

        // super-kernel/scan-isolation
        SuperKernel\ScanIsolation\Contract\ScanProcessorInterface::class,
        SuperKernel\ScanIsolation\Contract\ScannedInterface::class,
        SuperKernel\ScanIsolation\Exception\InvalidScanCallbackException::class,
        SuperKernel\ScanIsolation\Exception\ScanIsolationExceptionInterface::class,
        SuperKernel\ScanIsolation\Exception\ScanProcessFailedException::class,
        SuperKernel\ScanIsolation\Exception\ScanProcessorUnavailableException::class,
        SuperKernel\ScanIsolation\Execution\Callback::class,
        SuperKernel\ScanIsolation\Execution\CallbackRunner::class,
        SuperKernel\ScanIsolation\Process\EntryPoint::class,
        SuperKernel\ScanIsolation\Process\ReentryContext::class,
        SuperKernel\ScanIsolation\Execution\Scanned::class,
        SuperKernel\ScanIsolation\Processor\ExecScanProcessor::class,
        SuperKernel\ScanIsolation\Processor\NullScanProcessor::class,
        SuperKernel\ScanIsolation\Processor\PcntlScanProcessor::class,
        SuperKernel\ScanIsolation\Processor\PhaseScanProcessor::class,
        SuperKernel\ScanIsolation\Processor\ProcessScanProcessor::class,
        SuperKernel\ScanIsolation\Provider\ScanProcessorProvider::class,

        // super-kernel/tokenizer
        SuperKernel\Tokenizer\ClassNameParser::class,
        SuperKernel\Tokenizer\Contract\ClassNameParserInterface::class,
    ];

    /**
     * @throws Throwable When bootstrap, reflection, or diagnostic reporting fails
     */
    public function __invoke(?PsrContainerInterface $container = null): PsrContainerInterface
    {
        if (null === $container) {
            $scanProcessor = new ScanProcessorProvider()();
            $scanProcessor->execute(static function (
                ScannedInterface $scanned,
            ) use (&$container, $scanProcessor): void {
                $container = self::bootstrap($scanned, $scanProcessor);
            });
        }

        return $container;
    }

    /**
     * @return Container|null A cache-backed application container in the current phase, or null after publication
     *
     * @throws Throwable When bootstrap, reflection, or diagnostic reporting fails
     */
    private static function bootstrap(ScannedInterface $scanned, ScanProcessorInterface $scanProcessor): ?Container
    {
        $reflector = new ReflectorProvider()();
        $phase = new PhaseScanProcessor($scanned);
        $temporary = new Container(self::collector($reflector), [
            ReflectorInterface::class     => $reflector,
            Reflector\Reflector::class    => $reflector,
            ScanProcessorInterface::class => $phase,
        ]);
        $packages = $temporary->get(PackageManagerInterface::class);
        $vendorDir = $temporary->get(VendorDirInterface::class);
        $collector = $temporary->get(AnnotationCollectorProvider::class)(
            $packages,
            $phase,
            $reflector,
            $vendorDir,
        );

        if (!$collector instanceof ScanDiagnosticsInterface) {
            throw new LogicException('The bootstrap collector must expose scan diagnostics.');
        }

        $report = !$scanned->isScanned() || $scanProcessor instanceof NullScanProcessor;
        $diagnostics = $report ? $collector->getDiagnostics() : [];

        try {
            /** @var array<class-string, object> $entries */
            $entries = [
                AnnotationCollectorInterface::class => $collector,
                BasePathInterface::class            => $temporary->get(BasePathInterface::class),
                ComposerMetadataInterface::class    => $temporary->get(ComposerMetadataInterface::class),
                PackageManagerInterface::class      => $packages,
                ReflectorInterface::class           => $reflector,
                VendorDirInterface::class           => $vendorDir,
                ScanProcessorInterface::class       => $scanProcessor,
            ];

            foreach ($entries as $entry) {
                $entries[$entry::class] = $entry;
            }

            $container = new Container($collector, $entries);
            $reported = self::reportDiagnostics($container, $diagnostics);
        } catch (Throwable $exception) {
            try {
                DiagnosticOutput::writeToStderr($diagnostics);
            } catch (Throwable) {
                // Preserve the original container or logging failure.
            }

            throw $exception;
        }

        if (!$reported) {
            DiagnosticOutput::writeToStderr($diagnostics);
        }

        return $scanned->isScanned() ? $container : null;
    }

    /**
     * @param list<array{
     *     sequence: int, package: string, class: string, exception_type: string, message: string
     * }> $diagnostics
     *
     * @return bool Whether the batch was handed to a logger (or was empty); false requests emergency fallback
     *
     * @throws Throwable When the configured logger cannot be resolved or cannot accept a record
     */
    private static function reportDiagnostics(PsrContainerInterface $container, array $diagnostics): bool
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

        foreach ($diagnostics as $diagnostic) {
            $logger->info(sprintf(
                'Annotation scan skipped %s: %s: %s',
                self::displayDiagnostic($diagnostic['class']),
                self::displayDiagnostic($diagnostic['exception_type']),
                self::displayDiagnostic($diagnostic['message']),
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

    private static function displayDiagnostic(string $value): string
    {
        return preg_replace_callback('/[\x00-\x1f\x7f]/', static fn(array $match): string => match ($match[0]) {
            "\n"    => '\\n',
            "\r"    => '\\r',
            "\t"    => '\\t',
            default => sprintf('\\x%02X', ord($match[0])),
        }, $value);
    }

    /**
     * @throws ReflectionException When a bootstrap class or attribute type cannot be reflected
     */
    private static function collector(ReflectorInterface $reflector): AnnotationCollectorInterface
    {
        $scanner = new ClassAnnotationScanner($reflector);
        $classes = [];
        $methods = [];
        $properties = [];

        foreach (self::CLASS_NAMES as $class) {
            $record = $scanner->collect($reflector->reflectClass($class));
            $classes += $record['classes'];
            $methods += $record['methods'];
            $properties += $record['properties'];
        }

        return new AnnotationCollector([
            new PackageAnnotations('super-kernel/di', null, $classes, $methods, $properties),
        ]);
    }
}
