<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Helper;

use LogicException;
use Nusje2000\ComposerMonolith\Helper\PackageFinder;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPUnit\Framework\TestCase;

final class PackageFinderTest extends TestCase
{
    public function testGetPackageClosestToFile(): void
    {
        $finder = new PackageFinder();

        $subject = new DependencyGraph(
            '/root',
            new PackageCollection([
                new Package('root', '/root/src', false),
                new Package('sub-root', '/root/src/sub-pacakge', false),
                new Package('foo', '/root/vendor/foo', true),
                new Package('foo-bar', '/root/vendor/foo/bar', true),
            ])
        );

        self::assertSame('root', $finder->getPackageClosestToFile($subject, '/root/src/other-sub-pacakge/file.php')->getName());
        self::assertSame('sub-root', $finder->getPackageClosestToFile($subject, '/root/src/sub-pacakge/file.php')->getName());
        self::assertSame('foo', $finder->getPackageClosestToFile($subject, '/root/vendor/foo/file.php')->getName());
        self::assertSame('foo-bar', $finder->getPackageClosestToFile($subject, '/root/vendor/foo/bar/file.php')->getName());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Could not find package associated with file "/some-file.php".');
        $finder->getPackageClosestToFile($subject, '/some-file.php');
    }

    public function testGetPackageAssociatedWithFile(): void
    {
        $finder = new PackageFinder();

        $rootDir = dirname(__DIR__, 2);
        $subject = new DependencyGraph(
            $rootDir,
            new PackageCollection([
                new Package('root', $rootDir, false),
                new Package('sub-root', $rootDir . DIRECTORY_SEPARATOR . 'src', false),
                new Package('helper', $rootDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Helper', false),
            ])
        );

        $associated = $finder->getPackagesAssociatedWithClass($subject, PackageFinder::class);
        self::assertCount(2, $associated);
        self::assertTrue($associated->hasPackageByName('sub-root'));
        self::assertTrue($associated->hasPackageByName('helper'));

        $associated = $finder->getPackagesAssociatedWithClass($subject, 'non-existant-class');
        self::assertCount(0, $associated);
    }
}
