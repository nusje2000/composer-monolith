<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Helper;

use LogicException;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use ReflectionClass;

final class PackageFinder implements PackageFinderInterface
{
    /**
     * Returns the package definition that is the closest to the provided scope.
     */
    public function getPackageClosestToFile(DependencyGraph $graph, string $file): PackageInterface
    {
        /** @var PackageInterface|null $closestPackage */
        $closestPackage = null;

        foreach ($graph->getPackages() as $package) {
            // check if start of path matches
            if (0 === strpos($file, $package->getPackageLocation())) {
                // check if path is closer to the actual file location
                if (null === $closestPackage || strlen($package->getPackageLocation()) > strlen($closestPackage->getPackageLocation())) {
                    $closestPackage = $package;
                }
            }
        }

        if (null === $closestPackage) {
            throw new LogicException(sprintf('Could not find package associated with file "%s".', $file));
        }

        return $closestPackage;
    }

    /**
     * Returns all packages associated with the given class.
     *
     * One class can be defined in multiple packages, i.e. symfony does this in their monolithic repository.
     * The symfony/finder component in example is defined in symfony/finder and symfony/symfony
     */
    public function getPackagesAssociatedWithClass(DependencyGraph $graph, string $className): PackageCollection
    {
        if (!class_exists($className) && !interface_exists($className)) {
            return new PackageCollection();
        }

        $reflection = new ReflectionClass($className);
        $file = $reflection->getFileName();

        if (false === $file) {
            return new PackageCollection();
        }

        // remove phar scheme and make sure that the directory separator matches that of the current system
        $file = str_replace(['phar://', '/'], ['', DIRECTORY_SEPARATOR], $file);

        return $graph->getPackages()->filter(static function (PackageInterface $package) use ($graph, $file) {
            return 0 === strpos($file, $package->getPackageLocation()) && $package !== $graph->getRootPackage();
        });
    }
}
