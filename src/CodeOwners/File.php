<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\CodeOwners;

final class File
{
    /**
     * @var RuleCollection
     */
    protected $rules;

    public function __construct(RuleCollection $rules)
    {
        $this->rules = $rules;
    }

    public function getRules(): RuleCollection
    {
        return $this->rules;
    }

    public function toString(): string
    {
        $rules = [];
        foreach ($this->getRules() as $rule) {
            $rules[] = $rule->toString();
        }

        return implode(PHP_EOL, $rules);
    }
}
