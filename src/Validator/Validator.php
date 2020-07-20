<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator;

use Nusje2000\DependencyGraph\DependencyGraph;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Validator
{
    /**
     * @var RuleCollection
     */
    private $rules;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(RuleCollection $rules, ?LoggerInterface $logger = null)
    {
        $this->rules = $rules;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getViolations(DependencyGraph $graph): ViolationCollection
    {
        $violations = new ViolationCollection();
        foreach ($this->rules as $rule) {
            $this->logger->notice(sprintf('Executing %s.', get_class($rule)));
            $violations->merge($rule->execute($graph));
        }

        return $violations;
    }
}
