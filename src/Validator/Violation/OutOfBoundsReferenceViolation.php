<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Validator\Violation;

use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Package\PackageCollection;
use Nusje2000\DependencyGraph\Package\PackageInterface;

final class OutOfBoundsReferenceViolation implements ViolationInterface
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var int
     */
    private $line;

    /**
     * @var string
     */
    private $reference;

    /**
     * @var PackageInterface
     */
    private $basePackage;

    /**
     * @var PackageCollection
     */
    private $requiredPackages;

    public function __construct(string $file, int $line, string $reference, PackageInterface $basePackage, PackageCollection $requiredPackages)
    {
        $this->file = $file;
        $this->line = $line;
        $this->reference = $reference;
        $this->basePackage = $basePackage;
        $this->requiredPackages = $requiredPackages;
    }

    public function getMessage(): string
    {
        return sprintf(
            'Package %s references "%s" in "%s:%d" which is out of bounds, add one of [%s] to %s/composer.json',
            $this->getBasePackage()->getName(),
            $this->getReference(),
            $this->getFile(),
            $this->getLine(),
            implode(',', $this->getRequiredPackageNames()),
            $this->getBasePackage()->getPackageLocation()
        );
    }

    public function getFormattedMessage(): string
    {
        return sprintf(
            'Package <package>%s</package> references "<dependency>%s</dependency>" in "<file>%s</file>:%d" which is out of bounds, add one of [%s] to <file>%s/composer.json</file>',
            $this->getBasePackage()->getName(),
            $this->getReference(),
            $this->getFile(),
            $this->getLine(),
            implode(',', $this->getRequiredPackageNames('<dependency>%s</dependency>')),
            $this->getBasePackage()->getPackageLocation()
        );
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getBasePackage(): PackageInterface
    {
        return $this->basePackage;
    }

    public function getRequiredPackages(): PackageCollection
    {
        return $this->requiredPackages;
    }

    /**
     * @return array<string>
     */
    private function getRequiredPackageNames(string $format = '%s'): array
    {
        return $this->getRequiredPackages()->map(static function (PackageInterface $package) use ($format) {
            return sprintf($format, $package->getName());
        });
    }
}
