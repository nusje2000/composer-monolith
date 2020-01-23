<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\Rule\MissingReplaceRule;
use Nusje2000\DependencyGraph\Dependency\Dependency;
use Nusje2000\DependencyGraph\Dependency\DependencyCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\Package;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Replace\Replace;
use Nusje2000\DependencyGraph\Replace\ReplaceCollection;
use PHPUnit\Framework\TestCase;

final class MissingReplaceRuleTest extends TestCase
{
    public function testExecute(): void
    {
        $graph = new DependencyGraph('/path/to/root', new PackageCollection([
            new Package('foo/framework', '/path/to/root', false, new DependencyCollection([
                new Dependency('foo/foo-package', '1.0', false, new DependencyTypeEnum(DependencyTypeEnum::PACKAGE)),
            ]), null, new ReplaceCollection([
                new Replace('foo/package-1', 'self.version'),
            ])),
            new Package('foo/package-1', '/path/to/root/package-1', false),
            new Package('foo/package-2', '/path/to/root/package-1', false),
        ]));

        $rule = new MissingReplaceRule();
        $violations = $rule->execute($graph);

        self::assertSame([
            'Replace definition for package "foo/package-2" is missing in root definition.',
        ], $violations->getMessages()->toArray());

        self::assertSame([
            'Replace definition for package <package>"foo/package-2"</package> is missing in root definition.',
        ], $violations->getFormattedMessages()->toArray());
    }
}
