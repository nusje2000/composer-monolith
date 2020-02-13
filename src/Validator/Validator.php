<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator;

use Nusje2000\DependencyGraph\DependencyGraph;

final class Validator
{
    /**
     * @var RuleCollection
     */
    protected $rules;

    public function __construct(RuleCollection $rules)
    {
        $this->rules = $rules;
    }

    public function getErrors(DependencyGraph $graph): ViolationCollection
    {
        $violations = new ViolationCollection();
        foreach ($this->rules as $rule) {
            $violations->merge($rule->execute($graph));
        }

        return $violations;
    }
}
