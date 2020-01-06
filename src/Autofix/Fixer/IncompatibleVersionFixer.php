<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Validator\Violation\IncompatibleVersionConstraintViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;

final class IncompatibleVersionFixer extends AbstractFixer
{
    public function fix(DependencyGraph $graph, ViolationCollection $violations): void
    {
        /** @var array<string, array<int, ViolationInterface>> $versionConflicts */
        $versionConflicts = [];

        foreach ($violations as $violation) {
            if ($violation instanceof IncompatibleVersionConstraintViolation) {
                $versionConflicts[$violation->getDependency()->getName()][] = $violation;
            }
        }

        if (empty($versionConflicts)) {
            return;
        }

        $rootDefinition = PackageDefinition::createFromDirectory($graph->getRootPath());
        foreach ($versionConflicts as $dependencyName => $violationFixes) {
            $versionConstraint = $this->resolveRequiredVersion($graph, $dependencyName);

            if (null === $versionConstraint) {
                $this->error(sprintf('Could not resolve version constraint for dependency on <dependency>"%s"</dependency>.', $dependencyName));

                continue;
            }

            if ($rootDefinition->hasDependency($dependencyName)) {
                $this->solution(sprintf(
                    'Update dependency on <dependency>"%s"</dependency> to version <version>%s</version>',
                    $dependencyName,
                    $versionConstraint
                ));

                $rootDefinition->setDependency($dependencyName, $versionConstraint);
            }

            if ($rootDefinition->hasDevDependency($dependencyName)) {
                $this->solution(sprintf(
                    'Update dev-dependency on <dependency>"%s"</dependency> to version <version>%s</version>',
                    $dependencyName,
                    $versionConstraint
                ));

                $rootDefinition->setDevDependency($dependencyName, $versionConstraint);
            }

            foreach ($violationFixes as $violation) {
                $violations->remove($violation);
            }
        }

        $rootDefinition->save();
    }
}
