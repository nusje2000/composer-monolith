<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Violation;

use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Package\PackageInterface;

final class MissingReplaceViolation implements ViolationInterface
{
    /**
     * @var PackageInterface
     */
    protected $package;

    public function __construct(PackageInterface $package)
    {
        $this->package = $package;
    }

    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    public function getMessage(): string
    {
        return sprintf(
            'Replace definition for package "%s" is missing in root definition.',
            $this->package->getName()
        );
    }

    public function getFormattedMessage(): string
    {
        return sprintf(
            'Replace definition for package <package>"%s"</package> is missing in root definition.',
            $this->package->getName()
        );
    }
}
