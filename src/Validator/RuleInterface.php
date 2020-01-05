<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator;

use Nusje2000\DependencyGraph\DependencyGraph;

interface RuleInterface
{
    public function execute(DependencyGraph $graph): ViolationCollection;
}
