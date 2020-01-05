<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\Rule;

use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Validator\Rule\DevelopmentOnlyRule;
use PHPStan\Testing\TestCase;

final class DevelopmentOnlyRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $graph = new DependencyGraph('/path/to/root', new PackageCollection([
            new Package('foo/framework', '/path/to/root', false, new DependencyCollection([
                new Dependency('bar/bar-package', '1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-1', '/path/to/root/package-1', false, new DependencyCollection([
                new Dependency('bar/bar-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-2', '/path/to/root/package-2', false, new DependencyCollection([
                new Dependency('bar/bar-package', '1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
        ]));

        $rule = new DevelopmentOnlyRule();
        $violations = $rule->execute($graph);

        self::assertSame([
            'Dependency "bar/bar-package" is used by package "foo/package-1" as non-dev dependency but the package is defined as dev dependency in the root.',
        ], $violations->getMessages()->toArray());
    }
}
