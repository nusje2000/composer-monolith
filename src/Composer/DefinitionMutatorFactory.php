<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Finder\SplFileInfo;

final class DefinitionMutatorFactory implements DefinitionMutatorFactoryInterface
{
    public function createByPackage(PackageInterface $package): DefinitionMutatorInterface
    {
        return new DefinitionMutator(
            new SplFileInfo(
                $package->getPackageLocation() . DIRECTORY_SEPARATOR . 'composer.json',
                $package->getPackageLocation(),
                'composer.json'
            )
        );
    }
}
