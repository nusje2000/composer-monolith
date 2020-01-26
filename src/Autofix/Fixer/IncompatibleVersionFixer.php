<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Validator\Violation\IncompatibleVersionConstraintViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
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

        $rootPackage = $graph->getRootPackage();
        $mutator = $this->definitionMutatorFactory->createByPackage($graph->getRootPackage());

        foreach ($versionConflicts as $dependencyName => $violationFixes) {
            if (!$rootPackage->hasDependency($dependencyName)) {
                continue;
            }

            $dependency = $rootPackage->getDependency($dependencyName);
            $versionConstraint = $this->resolveRequiredVersion($graph, $dependencyName);

            if (null === $versionConstraint) {
                $this->error(sprintf('Could not resolve version constraint for dependency on <dependency>"%s"</dependency>', $dependencyName));

                continue;
            }

            if (!$dependency->isDev()) {
                $this->solution(sprintf(
                    'Update dependency on <dependency>"%s"</dependency> to version <version>%s</version>',
                    $dependencyName,
                    $versionConstraint
                ));

                $mutator->setDependency($dependencyName, $versionConstraint);
            } else {
                $this->solution(sprintf(
                    'Update dev-dependency on <dependency>"%s"</dependency> to version <version>%s</version>',
                    $dependencyName,
                    $versionConstraint
                ));

                $mutator->setDevDependency($dependencyName, $versionConstraint);
            }

            foreach ($violationFixes as $violation) {
                $violations->remove($violation);
            }
        }

        $mutator->save();
    }
}
