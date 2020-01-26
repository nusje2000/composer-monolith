<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Autofix\Fixer\IncompatibleVersionFixer;
use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorFactoryInterface;
use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\IncompatibleVersionConstraintViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyInterface;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\OutputStyle;

final class IncompatibleVersionFixerTest extends TestCase
{
    public function testFix(): void
    {
        /** @var MockObject&OutputStyle $output */
        $output = $this->createMock(OutputStyle::class);
        $output->expects(self::exactly(3))->method('writeln')->withConsecutive(
            ['<success>[SOLUTION]</success> Update dependency on <dependency>"some/dependency"</dependency> to version <version>^1.3</version>'],
            ['<success>[SOLUTION]</success> Update dev-dependency on <dependency>"some/dev-dependency"</dependency> to version <version>^1.3</version>'],
            ['<error>[ERROR]</error> Could not resolve version constraint for dependency on <dependency>"some/unresolvable-dependency"</dependency>']
        );

        /** @var MockObject&DefinitionMutatorInterface $mutator */
        $mutator = $this->createMock(DefinitionMutatorInterface::class);
        $mutator->expects(self::once())->method('setDependency')->with('some/dependency', '^1.3');
        $mutator->expects(self::once())->method('setDevDependency')->with('some/dev-dependency', '^1.3');

        $rootPackage = new Package('root/package', __DIR__, false, new DependencyCollection([
            new Dependency('some/dependency', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            new Dependency('some/dev-dependency', '^1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            new Dependency('some/unresolvable-dependency', '^1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
        ]));

        /** @var MockObject&DefinitionMutatorFactoryInterface $mutatorFactory */
        $mutatorFactory = $this->createMock(DefinitionMutatorFactoryInterface::class);
        $mutatorFactory->expects(self::once())->method('createByPackage')->with($rootPackage)->willReturn($mutator);

        $incompatibleDependency = new Dependency('some/dependency', '^1.3', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $incompatibleDevDependency = new Dependency('some/dev-dependency', '^1.3', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $unresolvableDependency = new Dependency('some/unresolvable-dependency', '^2.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $incompatiblePackage = new Package('incompatible/package', __DIR__ . '/incompatible', false, new DependencyCollection([
            $incompatibleDependency,
            $incompatibleDevDependency,
        ]));

        $graph = new DependencyGraph(
            __DIR__,
            new PackageCollection([
                $rootPackage,
                $incompatiblePackage,
            ])
        );

        $violations = new ViolationCollection([
            new IncompatibleVersionConstraintViolation($incompatiblePackage, $incompatibleDependency, '^1.0'),
            new IncompatibleVersionConstraintViolation($incompatiblePackage, $incompatibleDevDependency, '^1.0'),
            new IncompatibleVersionConstraintViolation($incompatiblePackage, $unresolvableDependency, '^1.0'),
            new IncompatibleVersionConstraintViolation($this->createStub(PackageInterface::class), $this->createStub(DependencyInterface::class), '^1.0'),
            $this->createStub(ViolationInterface::class),
        ]);

        $fixer = new IncompatibleVersionFixer($output, $mutatorFactory);
        $fixer->fix($graph, $violations);

        self::assertSame([
            2 => 'Dependency on "some/unresolvable-dependency" in package "incompatible/package" requires version that matches "^2.0". (installed: ^1.0)',
            3 => 'Dependency on "" in package "" requires version that matches "". (installed: ^1.0)',
            4 => '',
        ], $violations->getMessages()->toArray());
    }
}
