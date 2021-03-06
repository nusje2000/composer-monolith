<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\Rule\MissingDependencyRule;
use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Replace\Replace;
use Nusje2000\DependencyGraph\Replace\ReplaceCollection;
use PHPStan\Testing\TestCase;

final class MissingDependencyRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $graph = new DependencyGraph('/path/to/root', new PackageCollection([
            new Package('foo/framework', '/path/to/root', false, new DependencyCollection([
                new Dependency('foo/foo-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
                new Dependency('baz/baz-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-1', '/path/to/root/package-1', false, new DependencyCollection([
                new Dependency('foo/package-2', '~1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
                new Dependency('foo/bar-package', '~1.0', true, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-2', '/path/to/root/package-2', false, new DependencyCollection([
                new Dependency('bar/bar-package', '^1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-3', '/path/to/root/package-3', false, new DependencyCollection([
                new Dependency('foo/foo-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
                new Dependency('bar/bar-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-4', '/path/to/root/package-3', false, new DependencyCollection([
                new Dependency('beep/beep-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
                new Dependency('boop/boop-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('baz/baz-package', '/path/to/vendor/baz', true, null, null, new ReplaceCollection([
                new Replace('beep/beep-package', 'self.version'),
                new Replace('boop/boop-package', '1.0'),
            ])),
        ]));

        $rule = new MissingDependencyRule();
        $violations = $rule->execute($graph);

        self::assertSame([
            'Package "foo/package-1" requires a dependency on "foo/bar-package" (version: ~1.0, dev-only: yes)',
            'Package "foo/package-2" requires a dependency on "bar/bar-package" (version: ^1.0, dev-only: no)',
            'Package "foo/package-3" requires a dependency on "bar/bar-package" (version: 1.0, dev-only: no)',
        ], $violations->getMessages()->toArray());

        self::assertSame([
            'Package <package>"foo/package-1"</package> requires a dependency on <dependency>"foo/bar-package"</dependency> (version: <version>~1.0</version>, dev-only: yes)',
            'Package <package>"foo/package-2"</package> requires a dependency on <dependency>"bar/bar-package"</dependency> (version: <version>^1.0</version>, dev-only: no)',
            'Package <package>"foo/package-3"</package> requires a dependency on <dependency>"bar/bar-package"</dependency> (version: <version>1.0</version>, dev-only: no)',
        ], $violations->getFormattedMessages()->toArray());
    }
}
