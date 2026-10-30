<?php

declare(strict_types=1);

namespace SuperKernel\Di\Provider;

use LogicException;
use Psr;
use Psr\Container\ContainerInterface as PsrContainerInterface;
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
use SuperKernel\Contract\VendorDirInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Diagnostic\ScanDiagnosticReporter;
use SuperKernel\Reflector;
use SuperKernel\Reflector\Provider\ReflectorProvider;
use SuperKernel\ScanIsolation\Contract\ScannedInterface;
use SuperKernel\ScanIsolation\Contract\ScanProcessorInterface;
use SuperKernel\ScanIsolation\Processor\NullScanProcessor;
use SuperKernel\ScanIsolation\Processor\PhaseScanProcessor;
use SuperKernel\ScanIsolation\Provider\ScanProcessorProvider;
use Throwable;

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
        SuperKernel\Annotator\Cache\CacheRecordDecoder::class,
        SuperKernel\Annotator\Cache\PackageReferenceSnapshot::class,
        SuperKernel\Annotator\Cache\SerializedPackageAnnotations::class,
        SuperKernel\Annotator\Cache\SerializedPackageReferences::class,
        SuperKernel\Annotator\Cache\Storage\CachePath::class,
        SuperKernel\Annotator\Cache\Storage\CacheStorage::class,
        SuperKernel\Annotator\Cache\Storage\PackageRecordPath::class,
        SuperKernel\Annotator\Cache\Validation\AttributeListValidator::class,
        SuperKernel\Annotator\Cache\Validation\AttributeMapValidator::class,
        SuperKernel\Annotator\Cache\Validation\DiagnosticSequenceValidator::class,
        SuperKernel\Annotator\Cache\Validation\PackageDiagnosticsValidator::class,
        SuperKernel\Annotator\Cache\Validation\PackageNameValidator::class,
        SuperKernel\Annotator\Constants\CacheLayout::class,
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
        SuperKernel\Annotator\Scan\AttributeMetadataConverter::class,
        SuperKernel\Annotator\Scan\ClassAnnotationScanner::class,
        SuperKernel\Annotator\Snapshot\AttributeSnapshotDepth::class,
        SuperKernel\Annotator\Snapshot\AttributeSnapshotGraph::class,
        SuperKernel\Annotator\Snapshot\AttributeSnapshotRestorer::class,
        SuperKernel\Annotator\Snapshot\AttributeTypeList::class,

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
        SuperKernel\Composer\Cache\PackageCachePath::class,
        SuperKernel\Composer\Cache\PackageCacheStorage::class,
        SuperKernel\Composer\Cache\PackageReconciler::class,
        SuperKernel\Composer\ClassMap\CandidateDirectoryCollector::class,
        SuperKernel\Composer\ClassMap\CandidateFileCollector::class,
        SuperKernel\Composer\ClassMap\ClassMapBuilder::class,
        SuperKernel\Composer\ClassMap\PackageClassScanner::class,
        SuperKernel\Composer\Constants\AutoloadScope::class,
        SuperKernel\Composer\Constants\ComposerFile::class,
        SuperKernel\Composer\Constants\JsonType::class,
        SuperKernel\Composer\Constants\PackageType::class,
        SuperKernel\Composer\Constants\ScanRuleKind::class,
        SuperKernel\Composer\Constants\ScanRuleSource::class,
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
        SuperKernel\Composer\Manager\AutoloadFileOrder::class,
        SuperKernel\Composer\Manager\PackageManager::class,
        SuperKernel\Composer\Metadata\AutoloadRuleParser::class,
        SuperKernel\Composer\Metadata\Autoload\AutoloadFileResolver::class,
        SuperKernel\Composer\Metadata\Autoload\AutoloadPath::class,
        SuperKernel\Composer\Metadata\Autoload\AutoloadRuleInput::class,
        SuperKernel\Composer\Metadata\Autoload\ScanRuleCollector::class,
        SuperKernel\Composer\Metadata\ComposerAutoload::class,
        SuperKernel\Composer\Metadata\ComposerFileReader::class,
        SuperKernel\Composer\Metadata\ComposerJson::class,
        SuperKernel\Composer\Metadata\ComposerLock::class,
        SuperKernel\Composer\Metadata\ComposerMetadata::class,
        SuperKernel\Composer\Metadata\ComposerPackage::class,
        SuperKernel\Composer\Metadata\ComposerPackageName::class,
        SuperKernel\Composer\Metadata\PackageParser::class,
        SuperKernel\Composer\Metadata\Reader\ComposerFileDecoder::class,
        SuperKernel\Composer\Metadata\Reader\ComposerJsonReader::class,
        SuperKernel\Composer\Metadata\Reader\ComposerLockReader::class,
        SuperKernel\Composer\Metadata\Reader\JsonDocumentNormalizer::class,
        SuperKernel\Composer\Metadata\RequirementProcessor::class,
        SuperKernel\Composer\Metadata\RequirementSectionParser::class,
        SuperKernel\Composer\Metadata\RuntimePackageFields::class,
        SuperKernel\Composer\Metadata\Validation\ComposerDocumentValidator::class,
        SuperKernel\Composer\Metadata\Validation\JsonFieldAssertion::class,
        SuperKernel\Composer\Metadata\Validation\JsonStructureAssertion::class,
        SuperKernel\Composer\Metadata\Validation\JsonValueAssertion::class,
        SuperKernel\Composer\Model\Dependency::class,
        SuperKernel\Composer\Model\EnvironmentRequirement::class,
        SuperKernel\Composer\Model\Package::class,
        SuperKernel\Composer\Model\ScanRule::class,
        SuperKernel\Composer\Model\ScanRuleSet::class,
        SuperKernel\Composer\Path\AbsolutePath::class,
        SuperKernel\Composer\Path\BasePath::class,
        SuperKernel\Composer\Path\CanonicalDirectory::class,
        SuperKernel\Composer\Path\FilesystemPath::class,
        SuperKernel\Composer\Path\PathRoot::class,
        SuperKernel\Composer\Path\TrailingSeparator::class,
        SuperKernel\Composer\Path\VendorDir::class,
        SuperKernel\Composer\Path\WindowsDriveRoot::class,
        SuperKernel\Composer\Provider\BasePathProvider::class,
        SuperKernel\Composer\Provider\PackageManagerProvider::class,
        SuperKernel\Composer\Provider\VendorDirProvider::class,

        // super-kernel/contract
        SuperKernel\Contract\AnnotationCollectorInterface::class,
        SuperKernel\Contract\ApplicationInterface::class,
        SuperKernel\Contract\AttributeMetadataInterface::class,
        SuperKernel\Contract\AutoLoaderInterface::class,
        SuperKernel\Contract\BasePathInterface::class,
        SuperKernel\Contract\ConfigInterface::class,
        SuperKernel\Contract\ContainerInterface::class,
        SuperKernel\Contract\EnvironmentRequirementInterface::class,
        SuperKernel\Contract\EventInterface::class,
        SuperKernel\Contract\ExceptionDispatcherInterface::class,
        SuperKernel\Contract\ListenerInterface::class,
        SuperKernel\Contract\MultipleConfigInterface::class,
        SuperKernel\Contract\PackageAnnotationsInterface::class,
        SuperKernel\Contract\PackageManagerInterface::class,
        SuperKernel\Contract\PackageMetadataInterface::class,
        SuperKernel\Contract\PropertyAttributeHandlerInterface::class,
        SuperKernel\Contract\PropertyContextInterface::class,
        SuperKernel\Contract\ReflectorInterface::class,
        SuperKernel\Contract\ScanDiagnosticsInterface::class,
        SuperKernel\Contract\SingleConfigInterface::class,
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
        SuperKernel\Di\Definer\InstantiableClass::class,
        SuperKernel\Di\Definer\ProviderDefiner::class,
        SuperKernel\Di\Definition\ClassDefinition::class,
        SuperKernel\Di\Definition\FactoryDefinition::class,
        SuperKernel\Di\Definition\MethodDefinition::class,
        SuperKernel\Di\Definition\PropertyDefinition::class,
        SuperKernel\Di\Definition\ProviderDefinition::class,
        SuperKernel\Di\Diagnostic\ScanDiagnosticReporter::class,
        SuperKernel\Di\Exception\ContainerException::class,
        SuperKernel\Di\Exception\NotFoundException::class,
        SuperKernel\Di\Exception\ResolutionException::class,
        SuperKernel\Di\Factory\DefinitionFactory::class,
        SuperKernel\Di\Factory\ReservedCollector::class,
        SuperKernel\Di\Factory\ResolverFactory::class,
        SuperKernel\Di\PropertyHandler\AutowiredPropertyHandler::class,
        SuperKernel\Di\PropertyHandler\NativeLazyProxy::class,
        SuperKernel\Di\Property\PropertyContext::class,
        SuperKernel\Di\Property\PropertyDispatcher::class,
        SuperKernel\Di\Provider\ContainerProvider::class,
        SuperKernel\Di\Resolver\ClassResolver::class,
        SuperKernel\Di\Resolver\FactoryResolver::class,
        SuperKernel\Di\Resolver\MethodResolver::class,
        SuperKernel\Di\Resolver\PropertyResolver::class,
        SuperKernel\Di\Resolver\ProviderResolver::class,
        SuperKernel\Di\Storage\RetainedEntries::class,

        // super-kernel/reflector
        SuperKernel\Reflector\Cache\ClassReflectionCache::class,
        SuperKernel\Reflector\Cache\EnumReflectionCache::class,
        SuperKernel\Reflector\Cache\SymbolKey::class,
        SuperKernel\Reflector\Facade\ReflectorFacade::class,
        SuperKernel\Reflector\Provider\ReflectorProvider::class,
        SuperKernel\Reflector\Reflector::class,

        // super-kernel/scan-isolation
        SuperKernel\ScanIsolation\Constants\ReentryDescriptor::class,
        SuperKernel\ScanIsolation\Contract\ScanProcessorInterface::class,
        SuperKernel\ScanIsolation\Contract\ScannedInterface::class,
        SuperKernel\ScanIsolation\Exception\InvalidScanCallbackException::class,
        SuperKernel\ScanIsolation\Exception\ScanIsolationExceptionInterface::class,
        SuperKernel\ScanIsolation\Exception\ScanProcessFailedException::class,
        SuperKernel\ScanIsolation\Exception\ScanProcessorUnavailableException::class,
        SuperKernel\ScanIsolation\Execution\Callback::class,
        SuperKernel\ScanIsolation\Execution\CallbackRunner::class,
        SuperKernel\ScanIsolation\Execution\Scanned::class,
        SuperKernel\ScanIsolation\Process\EntryPoint::class,
        SuperKernel\ScanIsolation\Process\EntryPointProbe::class,
        SuperKernel\ScanIsolation\Process\ReentryContext::class,
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

        $bootstrapCollector = new AnnotationCollector([
            new PackageAnnotations('super-kernel/di', null, $classes, $methods, $properties),
        ]);
        $temporary = new Container($bootstrapCollector, [
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
            $reported = ScanDiagnosticReporter::report($container, $diagnostics);
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
}
