<?php

declare(strict_types=1);

namespace SuperKernel\Di\Tests\Integration;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use SuperKernel\Annotator\Cache\AnnotationPublisher;
use SuperKernel\Annotator\Model\AttributeMetadata;
use SuperKernel\Annotator\Model\PackageAnnotations;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\BasePathInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Contract\ReflectorInterface;
use SuperKernel\Contract\VendorDirInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Provider\ContainerProvider;
use SuperKernel\Di\Tests\Fixture\ContainerFactory;
use SuperKernel\Di\Tests\Fixture\FactoryProduct;
use SuperKernel\Di\Tests\Fixture\Provider\AnnotatedProvider;
use SuperKernel\Metadata\Build\MetadataPublisher;
use SuperKernel\Metadata\Exception\BuildRequiredException;
use SuperKernel\Metadata\Manager\PackageManager;
use SuperKernel\Metadata\Model\Package;
use SuperKernel\Metadata\Path\BasePath;
use SuperKernel\Metadata\Path\VendorDir;
use SuperKernel\Reflector\Facade\ReflectorFacade;
use SuperKernel\Reflector\Provider\ReflectorProvider;
use SuperKernel\Reflector\Reflector;
use Throwable;

use function array_replace;
use function bin2hex;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_exists;
use function implode;
use function is_dir;
use function json_decode;
use function random_bytes;
use function realpath;
use function rename;
use function rmdir;
use function sprintf;
use function unlink;
use function var_export;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Publishes generated artifacts into this component's own vendor directory, as `skernel build` does, and restores
 * the root container from them. Existing artifacts are moved aside for each test and put back afterwards.
 */
final class RestoreOnlyBootstrapTest extends TestCase
{
    private string $root;

    private string $vendor;

    private ?string $backup = null;

    protected function setUp(): void
    {
        $this->root = (string) realpath(dirname(__DIR__, 2));
        $this->vendor = $this->root . '/vendor';

        if (file_exists($this->vendor . '/.cache')) {
            $this->backup = $this->vendor . '/.cache-' . bin2hex(random_bytes(6));
            rename($this->vendor . '/.cache', $this->backup);
        }
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->vendor . '/.cache');

        if (null !== $this->backup) {
            rename($this->backup, $this->vendor . '/.cache');
        }
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRestoresARootContainerFromGeneratedArtifacts(): void
    {
        $this->publish();
        $container = new ContainerProvider()();

        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertSame($container, $container->get(PsrContainerInterface::class));

        $collector = $container->get(AnnotationCollectorInterface::class);
        $this->assertArrayHasKey(
            AnnotatedProvider::class,
            $collector->getClassesWithAttribute(FactoryProvider::class),
        );
        $this->assertInstanceOf(FactoryProduct::class, $container->get(FactoryProduct::class));

        $basePath = $container->get(BasePathInterface::class);
        $vendorDir = $container->get(VendorDirInterface::class);
        $packages = $container->get(PackageManagerInterface::class);
        $reflector = $container->get(ReflectorInterface::class);

        $this->assertSame($this->root, (string) $basePath);
        $this->assertSame($this->vendor, (string) $vendorDir);
        $this->assertSame($basePath, $container->get($basePath::class));
        $this->assertSame($vendorDir, $container->get($vendorDir::class));
        $this->assertSame($packages, $container->get($packages::class));
        $this->assertSame('super-kernel/di', $packages->root()->name());
        $this->assertInstanceOf(Reflector::class, $reflector);
        $this->assertSame($reflector, $container->get(Reflector::class));
        $this->assertSame($reflector, new ReflectionProperty(ReflectorFacade::class, 'reflector')->getValue());
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testMissingBuildNamesTheBuildCommand(): void
    {
        $this->expectException(BuildRequiredException::class);
        $this->expectExceptionMessage('Run `skernel build` to generate the project metadata.');

        new ContainerProvider()();
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testMissingAnnotationRecordsNameTheBuildCommand(): void
    {
        $this->publish();
        unlink($this->vendor . '/.cache/packages.ser');

        $this->expectException(BuildRequiredException::class);
        $this->expectExceptionMessage(
            'The generated annotation records cannot be restored. Run `skernel build` to generate the project metadata.',
        );

        new ContainerProvider()();
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRestorationLoadsNoAnnotatedClassBeforeItIsRequested(): void
    {
        $this->publish();
        $script = sprintf(
            <<<'PHP'
                require %s;
                $container = new SuperKernel\Di\Provider\ContainerProvider()();
                $before = class_exists(%s, false);
                $product = $container->get(%s);
                echo json_encode([
                    'before' => $before,
                    'after'  => class_exists(%s, false),
                    'product' => $product::class,
                ]);
                PHP,
            var_export($this->vendor . '/autoload.php', true),
            var_export(AnnotatedProvider::class, true),
            var_export(FactoryProduct::class, true),
            var_export(AnnotatedProvider::class, true),
        );
        $output = [];
        exec(implode(' ', [
            escapeshellarg(PHP_BINARY),
            '-r',
            escapeshellarg($script),
            '2>&1',
        ]), $output, $status);

        $this->assertSame(0, $status, implode("\n", $output));
        $this->assertSame([
            'before'  => false,
            'after'   => true,
            'product' => FactoryProduct::class,
        ], json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Publishes the root package, the DI annotations a build would collect, and one annotated factory fixture.
     *
     * @throws Throwable When publication fails
     */
    private function publish(): void
    {
        $classes = [];

        foreach ([
            ContainerProvider::class,
            ReflectorProvider::class,
            AnnotatedProvider::class,
        ] as $class) {
            foreach (new ReflectionClass($class)->getAttributes(FactoryProvider::class) as $attribute) {
                $classes[$class][] = new AttributeMetadata($attribute->newInstance());
            }
        }

        $package = ContainerFactory::collector()->getPackage('super-kernel/di')
            ?? throw new RuntimeException('The fixture collector has no DI package.');
        $manager = new PackageManager(new Package('super-kernel/di', null, null, 'library', $this->root), [], [
            'super-kernel/di' => [
                AnnotatedProvider::class => $this->root . '/tests/Fixture/Provider/AnnotatedProvider.php',
            ],
        ]);
        $vendorDir = new VendorDir($this->vendor);
        $publisher = new MetadataPublisher($vendorDir, new BasePath($this->root));

        $publisher->invalidate();
        $publisher->publishPackages($manager);
        new AnnotationPublisher($vendorDir)->publish($manager, [
            new PackageAnnotations(
                'super-kernel/di',
                null,
                array_replace($package->classAttributes(), $classes),
                $package->methodAttributes(),
                $package->propertyAttributes(),
            ),
        ]);
        $publisher->commit('dev');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($entries as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($directory);
    }
}
