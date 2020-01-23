<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Validator\Violation\MissingDependencyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;

final class MissingDependencyFixer extends AbstractFixer
{
    public function fix(DependencyGraph $graph, ViolationCollection $violations): void
    {
        /** @var array<string, array<int, ViolationInterface>> $missingDependencies */
        $missingDependencies = [];

        foreach ($violations as $violation) {
            if ($violation instanceof MissingDependencyViolation) {
                $dependency = $violation->getDependency();
                $missingDependencies[$dependency->getName()][] = $violation;
            }
        }

        if (empty($missingDependencies)) {
            return;
        }

        $mutator = $this->definitionMutatorFactory->createByPackage($graph->getRootPackage());

        foreach ($missingDependencies as $dependencyName => $violationFixes) {
            $versionConstraint = $this->resolveRequiredVersion($graph, $dependencyName);

            if (null === $versionConstraint) {
                $this->error(sprintf('Could not resolve version constraint for dependency on <dependency>"%s"</dependency>.', $dependencyName));

                continue;
            }

            $isDev = $graph->getSubPackages()->filter(static function (PackageInterface $package) use ($dependencyName) {
                return $package->hasDependency($dependencyName) && !$package->getDependency($dependencyName)->isDev();
            })->isEmpty();

            if ($isDev) {
                $this->solution(sprintf(
                    'Added pacakge <dependency>"%s"</dependency> to the dev-dependencies (version: <version>%s</version>)',
                    $dependencyName,
                    $versionConstraint
                ));

                $mutator->setDevDependency($dependencyName, $versionConstraint);
            } else {
                $this->solution(sprintf(
                    'Added pacakge <dependency>"%s"</dependency> to the dependencies (version: <version>%s</version>)',
                    $dependencyName,
                    $versionConstraint
                ));

                $mutator->setDependency($dependencyName, $versionConstraint);
            }

            foreach ($violationFixes as $violation) {
                $violations->remove($violation);
            }
        }

        $mutator->save();
    }
}
