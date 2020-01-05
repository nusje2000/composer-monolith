<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix;

use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;

interface FixerInterface
{
    public function fix(DependencyGraph $graph, ViolationCollection $violations): void;
}
