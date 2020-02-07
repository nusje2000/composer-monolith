<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Autofix\Fixer\MissingReplaceFixer;
use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorFactoryInterface;
use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingReplaceViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\OutputStyle;

final class MissingReplaceFixerTest extends TestCase
{
    public function testFix(): void
    {
        /** @var MockObject&OutputStyle $output */
        $output = $this->createMock(OutputStyle::class);
        $output->expects(self::once())->method('writeln')->withConsecutive(
            ['<success>[SOLUTION]</success> Added <package>"some/missing-replace"</package> to replace defintion (replaces version <version>self.version</version>)']
        );

        /** @var MockObject&DefinitionMutatorInterface $mutator */
        $mutator = $this->createMock(DefinitionMutatorInterface::class);
        $mutator->expects(self::once())->method('setReplace')->with('some/missing-replace', 'self.version');

        $rootPackage = new Package('root/package', __DIR__, false);

        /** @var MockObject&DefinitionMutatorFactoryInterface $mutatorFactory */
        $mutatorFactory = $this->createMock(DefinitionMutatorFactoryInterface::class);
        $mutatorFactory->expects(self::once())->method('createByPackage')->with($rootPackage)->willReturn($mutator);

        $missingReplace = new Package('some/missing-replace', __DIR__ . '/missing-replace', false);

        $graph = new DependencyGraph(
            __DIR__,
            new PackageCollection([
                $rootPackage,
                $missingReplace,
            ])
        );

        $violations = new ViolationCollection([
            new MissingReplaceViolation($missingReplace),
            $this->createStub(ViolationInterface::class),
        ]);

        $fixer = new MissingReplaceFixer($output, $mutatorFactory);
        $fixer->fix($graph, $violations);

        self::assertSame([
            1 => '',
        ], $violations->getMessages()->toArray());
    }
}
