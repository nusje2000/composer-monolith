<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Tests\Mutator;

use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorFactory;
use Nusje2000\DependencyGraph\Package\Package;
use PHPStan\Testing\TestCase;
use RuntimeException;

final class DefinitionMutatorFactoryTest extends TestCase
{
    public function testCreateByPackage(): void
    {
        $factory = new DefinitionMutatorFactory();

        $package = new Package('some/package', dirname(__DIR__, 2), false);
        $factory->createByPackage($package);
        $this->addToAssertionCount(1);

        $this->expectException(RuntimeException::class);
        $package = new Package('some/package', __DIR__, false);
        $factory->createByPackage($package);
    }
}
