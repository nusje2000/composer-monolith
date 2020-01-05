<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Composer\Semver\VersionParser;
use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\IncompatibleVersionConstraintViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
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
        $rootPackage = $graph->getRootPackage();
        $subPackages = $graph->getSubPackages();
        $violations = new ViolationCollection();

        foreach ($subPackages as $subPackage) {
            foreach ($subPackage->getDependencies() as $dependency) {
                if (!$rootPackage->hasDependency($dependency->getName())) {
                    continue;
                }

                $rootDependency = $rootPackage->getDependency($dependency->getName());
                if (!$this->isCompatible($rootDependency->getVersionConstraint(), $dependency->getVersionConstraint())) {
                    $violations->append(
                        new IncompatibleVersionConstraintViolation($subPackage, $dependency)
                    );
                }
            }
        }

        return $violations;
    }

    private function isCompatible(string $rootVersionConstraint, string $subPackageVersionConstraint): bool
    {
        $rootPacakgeConstraint = $this->versionParser->parseConstraints($rootVersionConstraint);
        $subPackageConstraint = $this->versionParser->parseConstraints($subPackageVersionConstraint);

        return $rootPacakgeConstraint->matches($subPackageConstraint);
    }
}
