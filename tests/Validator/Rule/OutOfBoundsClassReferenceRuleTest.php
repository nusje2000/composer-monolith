<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\Rule\OutOfBoundsClassReferenceRule;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPUnit\Framework\TestCase;

final class OutOfBoundsClassReferenceRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $rule = new OutOfBoundsClassReferenceRule();
        $rule->execute(
            new DependencyGraph(__DIR__, new PackageCollection([
                new Package('root', __DIR__, false),
            ]))
        );

        $this->addToAssertionCount(1);
    }
}
