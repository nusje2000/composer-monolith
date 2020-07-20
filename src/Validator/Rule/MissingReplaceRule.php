<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingReplaceViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class MissingReplaceRule implements RuleInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function execute(DependencyGraph $graph): ViolationCollection
    {
        $violations = new ViolationCollection();
        $rootPackage = $graph->getRootPackage();

        foreach ($graph->getSubPackages() as $subPackage) {
            $this->logger->info(sprintf('Validating replace for package "%s".', $subPackage->getName()));

            if (!$rootPackage->getReplaces()->hasReplaceByName($subPackage->getName())) {
                $this->logger->info(sprintf('Replace of "%s" is missing in root definition.', $subPackage->getName()));

                $violations->append(new MissingReplaceViolation($subPackage));
            }
        }

        return $violations;
    }
}
