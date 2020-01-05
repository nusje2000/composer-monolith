<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\Rule;

use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Validator\Rule\MissingDependencyRule;
use PHPStan\Testing\TestCase;

final class MissingDependencyRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $graph = new DependencyGraph('/path/to/root', new PackageCollection([
            new Package('foo/framework', '/path/to/root', false, new DependencyCollection([
                new Dependency('foo/foo-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-1', '/path/to/root/package-1', false, new DependencyCollection([
                new Dependency('foo/bar-package', '~1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-2', '/path/to/root/package-2', false, new DependencyCollection([
                new Dependency('bar/bar-package', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-3', '/path/to/root/package-3', false, new DependencyCollection([
                new Dependency('foo/foo-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
                new Dependency('bar/bar-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
        ]));

        $rule = new MissingDependencyRule();
        $violations = $rule->execute($graph);

        self::assertSame([
            'Package "foo/package-1" requires a dependency on "foo/bar-package" (version: ~1.0, dev-only: yes)',
            'Package "foo/package-2" requires a dependency on "bar/bar-package" (version: ^1.0, dev-only: no)',
            'Package "foo/package-3" requires a dependency on "bar/bar-package" (version: 1.0, dev-only: no)',
        ], $violations->getMessages()->toArray());
    }
}
