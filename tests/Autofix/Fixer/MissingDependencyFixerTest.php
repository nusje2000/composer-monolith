<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Autofix\Fixer\MissingDependencyFixer;
use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorFactoryInterface;
use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingDependencyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\OutputStyle;

final class MissingDependencyFixerTest extends TestCase
{
    public function testFix(): void
    {
        /** @var MockObject&OutputStyle $output */
        $output = $this->createMock(OutputStyle::class);
        $output->expects(self::exactly(3))->method('writeln')->withConsecutive(
            ['<success>[SOLUTION]</success> Added pacakge <dependency>"some/missing-dependency"</dependency> to the dependencies (version: <version>^1.0</version>)'],
            ['<success>[SOLUTION]</success> Added pacakge <dependency>"some/missing-dev-dependency"</dependency> to the dev-dependencies (version: <version>^1.0</version>)'],
            ['<error>[ERROR]</error> Could not resolve version constraint for dependency on <dependency>"some/missing-dependency-2"</dependency>']
        );

        /** @var MockObject&DefinitionMutatorInterface $mutator */
        $mutator = $this->createMock(DefinitionMutatorInterface::class);
        $mutator->expects(self::once())->method('setDependency')->with('some/missing-dependency', '^1.0');

        $rootPackage = new Package('root/package', __DIR__, false, new DependencyCollection([
            new Dependency('some/existing-dependency', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
        ]));

        /** @var MockObject&DefinitionMutatorFactoryInterface $mutatorFactory */
        $mutatorFactory = $this->createMock(DefinitionMutatorFactoryInterface::class);
        $mutatorFactory->expects(self::once())->method('createByPackage')->with($rootPackage)->willReturn($mutator);

        $missingDependency = new Dependency('some/missing-dependency', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $missingDevDependency = new Dependency('some/missing-dev-dependency', '^1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $missingDependency2 = new Dependency('some/missing-dependency-2', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $missingDependenciesPackage = new Package('some/package', __DIR__ . '/incompatible', false, new DependencyCollection([
            new Dependency('some/existing-dependency', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            $missingDependency,
            $missingDevDependency,
            $missingDependency2,
        ]));

        $conflictingDependency = new Dependency('some/missing-dependency-2', '^2.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE));
        $conflictingPackage = new Package('some/conflict', __DIR__ . '/incompatible', false, new DependencyCollection([
            $conflictingDependency,
        ]));

        $graph = new DependencyGraph(
            __DIR__,
            new PackageCollection([
                $rootPackage,
                $missingDependenciesPackage,
                $conflictingPackage,
            ])
        );

        $violations = new ViolationCollection([
            new MissingDependencyViolation($conflictingPackage, $missingDependency),
            new MissingDependencyViolation($missingDependenciesPackage, $missingDevDependency),
            new MissingDependencyViolation($missingDependenciesPackage, $missingDependency2),
            new MissingDependencyViolation($conflictingPackage, $conflictingDependency),
            $this->createStub(ViolationInterface::class),
        ]);

        $fixer = new MissingDependencyFixer($output, $mutatorFactory);
        $fixer->fix($graph, $violations);

        self::assertSame([
            2 => 'Package "some/package" requires a dependency on "some/missing-dependency-2" (version: ^1.0, dev-only: no)',
            3 => 'Package "some/conflict" requires a dependency on "some/missing-dependency-2" (version: ^2.0, dev-only: no)',
            4 => '',
        ], $violations->getMessages()->toArray());
    }
}
