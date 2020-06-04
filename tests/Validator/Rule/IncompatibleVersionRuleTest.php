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
use Nusje2000\DependencyGraph\Replace\Replace;
use Nusje2000\DependencyGraph\Replace\ReplaceCollection;
use PHPStan\Testing\TestCase;

final class IncompatibleVersionRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $graph = new DependencyGraph('/path/to/root', new PackageCollection([
            new Package('foo/framework', '/path/to/root', false, new DependencyCollection([
                new Dependency('bar/bar-package', '~1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
                new Dependency('baz/baz-package', '~1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
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
            new Package('foo/package-5', '/path/to/root/package-5', false, new DependencyCollection([
                new Dependency('foo/foo-package', '^2.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-6', '/path/to/root/package-6', false, new DependencyCollection([
                new Dependency('beep/beep-package', '^1.1', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-7', '/path/to/root/package-7', false, new DependencyCollection([
                new Dependency('beep/beep-package', '^2.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-8', '/path/to/root/package-8', false, new DependencyCollection([
                new Dependency('boop/boop-package', '^1.1', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('foo/package-9', '/path/to/root/package-9', false, new DependencyCollection([
                new Dependency('boop/boop-package', '^2.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ])),
            new Package('bar/bar-package', '/path/to/vendor/bar', true),
            new Package('foo/foo-package', '/path/to/vendor/foo', true),
            new Package('baz/baz-package', '/path/to/vendor/baz', true, null, null, new ReplaceCollection([
                new Replace('beep/beep-package', 'self.version'),
                new Replace('boop/boop-package', '^1.0'),
            ])),
        ]));

        $rule = new IncompatibleVersionRule();
        $violations = $rule->execute($graph);

        self::assertSame([
            'Dependency on "bar/bar-package" in package "foo/package-3" requires version that matches "2.0". (installed: ~1.0)',
            'Dependency on "bar/bar-package" in package "foo/package-4" requires version that matches "^2.0". (installed: ~1.0)',
            'Dependency on "beep/beep-package" in package "foo/package-7" requires version that matches "^2.0". (installed: ~1.0)',
            'Dependency on "boop/boop-package" in package "foo/package-9" requires version that matches "^2.0". (installed: ^1.0)',
        ], $violations->getMessages()->toArray());

        self::assertSame([
            'Dependency on <dependency>"bar/bar-package"</dependency> in package <package>"foo/package-3"</package> requires version that matches <version>2.0</version>. (installed: <version>~1.0</version>)',
            'Dependency on <dependency>"bar/bar-package"</dependency> in package <package>"foo/package-4"</package> requires version that matches <version>^2.0</version>. (installed: <version>~1.0</version>)',
            'Dependency on <dependency>"beep/beep-package"</dependency> in package <package>"foo/package-7"</package> requires version that matches <version>^2.0</version>. (installed: <version>~1.0</version>)',
            'Dependency on <dependency>"boop/boop-package"</dependency> in package <package>"foo/package-9"</package> requires version that matches <version>^2.0</version>. (installed: <version>^1.0</version>)',
        ], $violations->getFormattedMessages()->toArray());
    }
}
