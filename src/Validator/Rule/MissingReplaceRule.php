<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingReplaceViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;

final class MissingReplaceRule implements RuleInterface
{
    public function execute(DependencyGraph $graph): ViolationCollection
    {
        $violations = new ViolationCollection();
        $rootPackage = $graph->getRootPackage();

        foreach ($graph->getSubPackages() as $subPackage) {
            if (!$rootPackage->getReplaces()->hasReplaceByName($subPackage->getName())) {
                $violations->append(new MissingReplaceViolation($subPackage));
            }
        }

        return $violations;
    }
}
