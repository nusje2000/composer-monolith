<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingDependencyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;

final class MissingDependencyRule implements RuleInterface
{
    public function execute(DependencyGraph $graph): ViolationCollection
    {
        $rootPackage = $graph->getRootPackage();
        $subPackages = $graph->getSubPackages();
        $violations = new ViolationCollection();

        foreach ($subPackages as $subPackage) {
            foreach ($subPackage->getDependencies() as $dependency) {
                // skip references to internal packages
                if ($subPackages->hasPackageByName($dependency->getName())) {
                    continue;
                }

                if (!$rootPackage->hasDependency($dependency->getName())) {
                    $violations->append(new MissingDependencyViolation($subPackage, $dependency));
                }
            }
        }

        return $violations;
    }
}
