<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix;

use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;

final class ViolationFixer
{
    /**
     * @var FixerCollection
     */
    private $fixers;

    public function __construct(FixerCollection $fixers)
    {
        $this->fixers = $fixers;
    }

    /**
     * Returns the left over violations after fixes
     */
    public function fix(DependencyGraph $graph, ViolationCollection $violations): ViolationCollection
    {
        $violations = clone $violations;

        foreach ($this->fixers as $fixer) {
            $fixer->fix($graph, $violations);
        }

        return $violations;
    }
}
