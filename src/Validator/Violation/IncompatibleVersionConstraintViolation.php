<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Violation;

use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Dependency\DependencyInterface;
use Nusje2000\DependencyGraph\Package\PackageInterface;

final class IncompatibleVersionConstraintViolation implements ViolationInterface
{
    /**
     * @var PackageInterface
     */
    private $package;

    /**
     * @var DependencyInterface
     */
    private $dependency;

    /**
     * @var string
     */
    private $installedVersion;

    public function __construct(PackageInterface $package, DependencyInterface $dependency, string $installedVersion)
    {
        $this->package = $package;
        $this->dependency = $dependency;
        $this->installedVersion = $installedVersion;
    }

    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    public function getDependency(): DependencyInterface
    {
        return $this->dependency;
    }

    public function getInstalledVersion(): string
    {
        return $this->installedVersion;
    }

    public function getMessage(): string
    {
        return sprintf(
            'Dependency on "%s" in package "%s" requires version that matches "%s". (installed: %s)',
            $this->dependency->getName(),
            $this->package->getName(),
            $this->dependency->getVersionConstraint(),
            $this->getInstalledVersion()
        );
    }

    public function getFormattedMessage(): string
    {
        return sprintf(
            'Dependency on <dependency>"%s"</dependency> in package <package>"%s"</package> requires version that matches <version>%s</version>. ' .
            '(installed: <version>%s</version>)',
            $this->dependency->getName(),
            $this->package->getName(),
            $this->dependency->getVersionConstraint(),
            $this->getInstalledVersion()
        );
    }
}
