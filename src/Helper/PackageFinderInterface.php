<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Helper;

use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Package\PackageInterface;

interface PackageFinderInterface
{
    /**
     * Returns the package definition that is the closest to the provided scope.
     */
    public function getPackageClosestToFile(DependencyGraph $graph, string $file): PackageInterface;

    /**
     * Returns all packages associated with the given class.
     *
     * One class can be defined in multiple packages, i.e. symfony does this in their monolithic repository.
     * The symfony/finder component in example is defined in symfony/finder and symfony/symfony
     */
    public function getPackagesAssociatedWithClass(DependencyGraph $graph, string $className): PackageCollection;
}
