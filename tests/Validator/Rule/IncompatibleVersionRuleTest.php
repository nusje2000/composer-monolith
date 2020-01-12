<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\Rule\IncompatibleVersionRule;
use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use PHPStan\Testing\TestCase;

final class IncompatibleVersionRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $graph = new DependencyGraph('/path/to/root', new PackageCollection([
            new Package('foo/framework', '/path/to/root', false, new DependencyCollection([
                new Dependency('bar/bar-package', '~1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-1', '/path/to/root/package-1', false, new DependencyCollection([
                new Dependency('bar/bar-package', '1.1', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-2', '/path/to/root/package-2', false, new DependencyCollection([
                new Dependency('bar/bar-package', '^1.2', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-3', '/path/to/root/package-3', false, new DependencyCollection([
                new Dependency('bar/bar-package', '2.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-4', '/path/to/root/package-4', false, new DependencyCollection([
                new Dependency('bar/bar-package', '^2.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
        ]));

        $rule = new IncompatibleVersionRule();
        $violations = $rule->execute($graph);

        self::assertSame([
            'Dependency on "bar/bar-package" in package "foo/package-3" requires version that matches "2.0". (installed: ~1.0)',
            'Dependency on "bar/bar-package" in package "foo/package-4" requires version that matches "^2.0". (installed: ~1.0)',
        ], $violations->getMessages()->toArray());
    }
}
