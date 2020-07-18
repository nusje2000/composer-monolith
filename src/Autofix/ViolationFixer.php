<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix;

use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ViolationFixer
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FixerCollection
     */
    private $fixers;

    public function __construct(FixerCollection $fixers, ?LoggerInterface $logger = null)
    {
        $this->fixers = $fixers;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Returns the left over violations after fixes
     */
    public function fix(DependencyGraph $graph, ViolationCollection $violations): ViolationCollection
    {
        $violations = clone $violations;

        foreach ($this->fixers as $fixer) {
            $this->logger->notice(
                sprintf('Executing "%s".', get_class($fixer))
            );

            $currentViolationCount = $violations->count();
            $fixer->fix($graph, $violations);

            $this->logger->notice(
                sprintf('"%s" fixed %d violations.', get_class($fixer), $currentViolationCount - $violations->count())
            );
        }

        return $violations;
    }
}
