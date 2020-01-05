<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Violation;

use Nusje2000\DependencyGraph\Dependency\DependencyInterface;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;

final class MissingDependencyViolation implements ViolationInterface
{
    /**
     * @var PackageInterface
     */
    protected $package;

    /**
     * @var DependencyInterface
     */
    protected $dependency;

    public function __construct(PackageInterface $package, DependencyInterface $dependency)
    {
        $this->package = $package;
        $this->dependency = $dependency;
    }

    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    public function getDependency(): DependencyInterface
    {
        return $this->dependency;
    }

    public function getMessage(): string
    {
        return sprintf(
            'Package "%s" requires a dependency on "%s" (version: %s, dev-only: %s)',
            $this->package->getName(),
            $this->dependency->getName(),
            $this->dependency->getVersionConstraint(),
            $this->dependency->isDev() ? 'yes' : 'no'
        );
    }
}
