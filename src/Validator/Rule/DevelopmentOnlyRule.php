<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\DevelopmentOnlyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;

final class DevelopmentOnlyRule implements RuleInterface
{
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
                if ($rootDependency->isDev() && !$dependency->isDev()) {
                    $violations->append(new DevelopmentOnlyViolation($subPackage, $dependency));
                }
            }
        }

        return $violations;
    }
}
