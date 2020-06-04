<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Composer\Semver\VersionParser;
use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\IncompatibleVersionConstraintViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyInterface;
use Nusje2000\DependencyGraph\DependencyGraph;

final class IncompatibleVersionRule implements RuleInterface
{
    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct()
    {
        $this->versionParser = new VersionParser();
    }

    public function execute(DependencyGraph $graph): ViolationCollection
    {
        $subPackages = $graph->getSubPackages();
        $violations = new ViolationCollection();

        foreach ($subPackages as $subPackage) {
            foreach ($subPackage->getDependencies() as $dependency) {
                $rootVersionConstraint = $this->getRootVersionConstraint($graph, $dependency);
                if (null === $rootVersionConstraint) {
                    continue;
                }

                if (!$this->isCompatible($rootVersionConstraint, $dependency->getVersionConstraint())) {
                    $violations->append(
                        new IncompatibleVersionConstraintViolation($subPackage, $dependency, $rootVersionConstraint)
                    );
                }
            }
        }

        return $violations;
    }

    private function getRootVersionConstraint(DependencyGraph $graph, DependencyInterface $dependency): ?string
    {
        $rootPackage = $graph->getRootPackage();
        if ($rootPackage->hasDependency($dependency->getName())) {
            return $rootPackage->getDependencies()->getDependencyByName($dependency->getName())->getVersionConstraint();
        }

        foreach ($rootPackage->getDependencies() as $rootDependency) {
            if (!$graph->hasPackage($rootDependency->getName())) {
                continue;
            }

            $dependencyPackage = $graph->getPackage($rootDependency->getName());
            if ($dependencyPackage->getReplaces()->hasReplaceByName($dependency->getName())) {
                $replacedVersion = $dependencyPackage->getReplaces()->getReplaceByName(
                    $dependency->getName()
                )->getVersion();

                if ('self.version' === $replacedVersion) {
                    return $rootDependency->getVersionConstraint();
                }

                return $replacedVersion;
            }
        }

        return null;
    }

    private function isCompatible(string $rootVersionConstraint, string $subPackageVersionConstraint): bool
    {
        $rootPacakgeConstraint = $this->versionParser->parseConstraints($rootVersionConstraint);
        $subPackageConstraint = $this->versionParser->parseConstraints($subPackageVersionConstraint);

        return $rootPacakgeConstraint->matches($subPackageConstraint);
    }
}
