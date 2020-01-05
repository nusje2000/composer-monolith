<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Exception\DefinitionException;
use Nusje2000\ComposerMonolith\Validator\Violation\DevelopmentOnlyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;

final class DevelopmentOnlyFixer extends AbstractFixer
{
    public function fix(DependencyGraph $graph, ViolationCollection $violations): void
    {
        $rootDefinition = PackageDefinition::createFromDirectory($graph->getRootPath());

        /** @var array<int, string> $fixed */
        $fixed = [];
        foreach ($violations as $violation) {
            if ($violation instanceof DevelopmentOnlyViolation) {
                if (!in_array($violation->getDependency()->getName(), $fixed, true)) {
                    $name = $violation->getDependency()->getName();

                    try {
                        $versionConstraint = $rootDefinition->getDevDependencyVersionConstraint($name);
                    } catch (DefinitionException $exception) {
                        $this->output->writeln(sprintf('[ERROR] %s', $exception->getMessage()));

                        continue;
                    }

                    $rootDefinition->removeDevDependency($name);
                    $rootDefinition->setDependency($name, $versionConstraint);
                    $fixed[] = $name;

                    $this->output->writeln(sprintf('[SOLUTION] Moved dependency on "%s" from require-dev to require.', $name));
                }

                $violations->remove($violation);
            }
        }

        $rootDefinition->save();
    }
}
