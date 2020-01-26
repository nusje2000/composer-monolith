<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Autofix;

use Nusje2000\ComposerMonolith\Autofix\FixerCollection;
use Nusje2000\ComposerMonolith\Autofix\FixerInterface;
use Nusje2000\ComposerMonolith\Autofix\ViolationFixer;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPUnit\Framework\TestCase;

final class ViolationFixerTest extends TestCase
{
    public function testFix(): void
    {
        $violations = new ViolationCollection();
        $graph = new DependencyGraph(__DIR__, new PackageCollection());

        $fixer = $this->createMock(FixerInterface::class);
        $fixer->expects(self::once())->method('fix')->with($graph, $violations);

        $violationFixer = new ViolationFixer(new FixerCollection([$fixer]));
        $newViolations = $violationFixer->fix($graph, $violations);

        self::assertNotSame($violations, $newViolations);
    }
}
