<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Validator\Violation\MissingReplaceViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;

final class MissingReplaceFixer extends AbstractFixer
{
    public function fix(DependencyGraph $graph, ViolationCollection $violations): void
    {
        $rootDefinition = PackageDefinition::createFromDirectory($graph->getRootPath());
        foreach ($violations as $violation) {
            if (!$violation instanceof MissingReplaceViolation) {
                continue;
            }

            $rootDefinition->setReplace($violation->getPackage()->getName(), 'self.version');

            $this->solution(sprintf(
                'Added <package>"%s"</package> to replace defintion (replaces version <version>self.version</version>).',
                $violation->getPackage()->getName()
            ));

            $violations->remove($violation);
        }
        $rootDefinition->save();
    }
}
