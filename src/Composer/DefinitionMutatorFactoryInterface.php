<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use Nusje2000\DependencyGraph\Package\PackageInterface;

interface DefinitionMutatorFactoryInterface
{
    public function createByPackage(PackageInterface $package): DefinitionMutatorInterface;
}
