<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\NodeVisitor;

use LogicException;
use Nusje2000\ComposerMonolith\Helper\PackageFinderInterface;
use Nusje2000\ComposerMonolith\Validator\Violation\OutOfBoundsReferenceViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\DependencyGraph\DependencyGraph;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ClassUseVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    private $fileName;

    /**
     * @var DependencyGraph
     */
    private $graph;

    /**
     * @var PackageFinderInterface
     */
    private $packageFinder;

    /**
     * @var ViolationCollection
     */
    private $violations;

    public function __construct(DependencyGraph $graph, PackageFinderInterface $packageFinder)
    {
        $this->packageFinder = $packageFinder;
        $this->graph = $graph;
        $this->violations = new ViolationCollection();
    }

    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof Node\Name) {
            return null;
        }

        $referencedClass = (string)$node;

        // The reason this will return true is because this is not meant to validate class usages
        // only to validate the correct reference to existing classes/interfaces
        if (!class_exists($referencedClass) && !interface_exists($referencedClass)) {
            return null;
        }

        $basePackage = $this->packageFinder->getPackageClosestToFile($this->graph, $this->getCurrentFileName());
        $requiredPackages = $this->packageFinder->getPackagesAssociatedWithClass($this->graph, $referencedClass);
        if ($requiredPackages->isEmpty() || $requiredPackages->contains($basePackage)) {
            return null;
        }

        // check if one of the required packages is a dependency of the base package
        foreach ($requiredPackages as $requiredPackage) {
            if ($basePackage->hasDependency($requiredPackage->getName())) {
                return null;
            }
        }

        $this->violations->append(
            new OutOfBoundsReferenceViolation(
                $this->getCurrentFileName(),
                $node->getLine(),
                (string)$node,
                $basePackage,
                $requiredPackages
            )
        );

        return null;
    }

    /**
     * @return ViolationCollection
     */
    public function getOutOfBoundsViolations(): ViolationCollection
    {
        return $this->violations;
    }

    private function getCurrentFileName(): string
    {
        if (null === $this->fileName) {
            throw new LogicException('Current file is not set. Use setCurrentFile to set the file associated with the AST.');
        }

        return $this->fileName;
    }

    public function setCurrentFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function reset(): void
    {
        $this->fileName = null;
        $this->violations = new ViolationCollection();
    }
}
