<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Rule;

use Nusje2000\ComposerMonolith\Validator\RuleInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingDependencyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\Dependency\DependencyInterface;
use Nusje2000\DependencyGraph\Dependency\DependencyTypeEnum;
use Nusje2000\DependencyGraph\DependencyGraph;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class MissingDependencyRule implements RuleInterface
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
        $rootPackage = $graph->getRootPackage();
        $subPackages = $graph->getSubPackages();
        $violations = new ViolationCollection();

        foreach ($subPackages as $subPackage) {
            $this->logger->info(sprintf('Validating dependencies of "%s".', $subPackage->getName()));

            foreach ($subPackage->getDependencies() as $dependency) {
                // skip references to internal packages
                if ($subPackages->hasPackageByName($dependency->getName())) {
                    $this->logger->debug(sprintf('Skipped validation of "%s", package is internal.', $dependency->getName()));

                    continue;
                }

                if (
                    !$rootPackage->hasDependency($dependency->getName())
                    && !$this->isReplacedDependency($graph, $dependency)
                ) {
                    $this->logger->info(
                        sprintf('Package "%s" requires "%s" but dependency is not present in root definition.', $subPackage->getName(), $dependency->getName())
                    );

                    $violations->append(new MissingDependencyViolation($subPackage, $dependency));
                }
            }
        }

        return $violations;
    }

    private function isReplacedDependency(DependencyGraph $graph, DependencyInterface $dependency): bool
    {
        $rootPackage = $graph->getRootPackage();
        foreach ($rootPackage->getDependencies() as $rootDependency) {
            if (
                !$rootDependency->getType()->equals(new DependencyTypeEnum(DependencyTypeEnum::PACKAGE))
                || !$graph->hasPackage($rootDependency->getName())
            ) {
                continue;
            }

            $dependencyPackage = $graph->getPackage($rootDependency->getName());
            if ($dependencyPackage->getReplaces()->hasReplaceByName($dependency->getName())) {
                return true;
            }
        }

        return false;
    }
}
