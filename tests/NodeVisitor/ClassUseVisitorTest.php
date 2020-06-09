<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\NodeVisitor;

use LogicException;
use Nusje2000\ComposerMonolith\Helper\PackageFinderInterface;
use Nusje2000\ComposerMonolith\NodeVisitor\ClassUseVisitor;
use Nusje2000\ComposerMonolith\Validator\Violation\OutOfBoundsReferenceViolation;
use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PhpParser\Node;
use PhpParser\Node\Name;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ClassUseVisitorTest extends TestCase
{
    /**
     * @var PackageFinderInterface&MockObject
     */
    private $finder;

    /**
     * @var ClassUseVisitor
     */
    private $visitor;

    public function testWithSelfInRequiredPackages(): void
    {
        $package = new Package('some-name', '', false);
        $this->finder->method('getPackageClosestToFile')->willReturn($package);
        $this->finder->method('getPackagesAssociatedWithClass')->willReturn(new PackageCollection([$package]));

        $this->visitor->enterNode(new Name(ClassUseVisitor::class));
        self::assertCount(0, $this->visitor->getOutOfBoundsViolations());
    }

    public function testWithEmptyRequiredPackages(): void
    {
        $package = new Package('some-name', '', false);
        $this->finder->method('getPackageClosestToFile')->willReturn($package);
        $this->finder->method('getPackagesAssociatedWithClass')->willReturn(new PackageCollection([]));

        $this->visitor->enterNode(new Name(ClassUseVisitor::class));
        self::assertCount(0, $this->visitor->getOutOfBoundsViolations());
    }

    public function testWithRequiredPackageAsDependency(): void
    {
        $required = new Package('required-package', '', false);

        $this->finder->method('getPackageClosestToFile')->willReturn(new Package('base-package', '', false, new DependencyCollection([
            new Dependency('required-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
        ])));
        $this->finder->method('getPackagesAssociatedWithClass')->willReturn(new PackageCollection([$required]));

        $this->visitor->enterNode(new Name(ClassUseVisitor::class));
        self::assertCount(0, $this->visitor->getOutOfBoundsViolations());
    }

    public function testWithMissingRequiredPackage(): void
    {
        $required = new Package('required-package', '/vendor', true);
        $basePackage = new Package('base-package', '/source', false, new DependencyCollection());

        $this->finder->method('getPackageClosestToFile')->willReturn($basePackage);
        $this->finder->method('getPackagesAssociatedWithClass')->willReturn(new PackageCollection([$required]));

        $this->visitor->enterNode(new Name(ClassUseVisitor::class, [
            'startLine' => 123,
        ]));

        self::assertCount(1, $this->visitor->getOutOfBoundsViolations());

        /** @var OutOfBoundsReferenceViolation|mixed $violation */
        $violation = $this->visitor->getOutOfBoundsViolations()->first();
        self::assertNotNull($violation);
        self::assertInstanceOf(OutOfBoundsReferenceViolation::class, $violation);
        self::assertSame($violation->getBasePackage(), $basePackage);
        self::assertSame($violation->getLine(), 123);
        self::assertSame($violation->getFile(), 'some-file.php');
        self::assertSame($violation->getReference(), ClassUseVisitor::class);
        self::assertSame(
            $violation->getFormattedMessage(),
            'Package <package>base-package</package> references "<dependency>Nusje2000\ComposerMonolith\NodeVisitor\ClassUseVisitor</dependency>" in "<file>some-file.php</file>:123" which is out of bounds, add one of [<dependency>required-package</dependency>] to <file>/source/composer.json</file>'
        );
        self::assertSame(
            $violation->getMessage(),
            'Package base-package references "Nusje2000\ComposerMonolith\NodeVisitor\ClassUseVisitor" in "some-file.php:123" which is out of bounds, add one of [required-package] to /source/composer.json'
        );
    }

    public function testWithInvalidClass(): void
    {
        $package = new Package('some-name', '', false);
        $this->finder->method('getPackageClosestToFile')->willReturn($package);
        $this->finder->method('getPackagesAssociatedWithClass')->willReturn(new PackageCollection([$package]));

        $this->visitor->enterNode(new Name('blaat'));
        self::assertCount(0, $this->visitor->getOutOfBoundsViolations());
    }

    public function testWithInvalidNode(): void
    {
        $this->visitor->enterNode($this->createStub(Node::class));
        self::assertCount(0, $this->visitor->getOutOfBoundsViolations());
    }

    public function testWithoutSettingCurrentFile(): void
    {
        $this->visitor->reset();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Current file is not set. Use setCurrentFile to set the file associated with the AST.');
        $this->visitor->enterNode(new Name(ClassUseVisitor::class));
    }

    protected function setUp(): void
    {
        $this->finder = $this->createMock(PackageFinderInterface::class);
        $this->visitor = new ClassUseVisitor(
            new DependencyGraph('/root', new PackageCollection([])),
            $this->finder
        );
        $this->visitor->setCurrentFileName('some-file.php');
    }
}
