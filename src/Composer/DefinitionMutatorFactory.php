<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Composer;

use Nusje2000\DependencyGraph\Package\PackageInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\SplFileInfo;

final class DefinitionMutatorFactory implements DefinitionMutatorFactoryInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function createByPackage(PackageInterface $package): DefinitionMutatorInterface
    {
        return new DefinitionMutator(
            new SplFileInfo(
                $package->getPackageLocation() . DIRECTORY_SEPARATOR . 'composer.json',
                $package->getPackageLocation(),
                'composer.json'
            ),
            $this->logger
        );
    }
}
