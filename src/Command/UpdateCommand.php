<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class UpdateCommand extends AbstractDependencyGraphCommand
{
    protected static $defaultName = 'update';

    protected function configure(): void
    {
        $this->setDescription(
            'Check for packages that have differences in their version and be able to "equalize" these.'
        );

        $this->addArgument('dependency', InputArgument::REQUIRED, 'The depdendency you want to update.');
        $this->addArgument('version_constraint', InputArgument::REQUIRED, 'The new version constraint.');

        $this->addOption('autofix', 'f', InputOption::VALUE_NONE, 'Try to fix the validations automatically.');
        $this->addOption('root', 'r', InputOption::VALUE_REQUIRED, 'Set the root path relative to the current working directory.');
    }

    protected function doExecute(DependencyGraph $graph): int
    {
        $dependencyName = $this->input->getArgument('dependency');
        $versionConstraint = $this->input->getArgument('version_constraint');

        if (!is_string($dependencyName)) {
            $this->io->error('Dependency name must be a string.');

            return 1;
        }

        if (!is_string($versionConstraint)) {
            $this->io->error('Version constraint must be a string.');

            return 1;
        }

        $packages = $graph->getPackages()->filter(static function (PackageInterface $package): bool {
            return !$package->isFromVendor();
        });

        foreach ($packages as $package) {
            $definition = PackageDefinition::createFromDirectory($package->getPackageLocation());

            if ($definition->hasDependency($dependencyName)) {
                $currentVersion = $definition->getDependencyVersionConstraint($dependencyName);
                $definition->setDependency($dependencyName, $versionConstraint);

                $this->io->writeln(sprintf(
                    '<success>[SUCCESS]</success> changed dependency on <dependency>"%s"</dependency> from ' .
                    'version <version>%s</version> to <version>%s</version> in package <package>"%s"</package>',
                    $dependencyName,
                    $currentVersion,
                    $versionConstraint,
                    $package->getName()
                ));
            }

            if ($definition->hasDevDependency($dependencyName)) {
                $currentVersion = $definition->getDevDependencyVersionConstraint($dependencyName);
                $definition->setDevDependency($dependencyName, $versionConstraint);

                $this->io->writeln(sprintf(
                    '<success>[SUCCESS]</success> changed dev-dependency on <dependency>"%s"</dependency> from ' .
                    'version <version>%s</version> to <version>%s</version> in package <package>"%s"</package>',
                    $dependencyName,
                    $currentVersion,
                    $versionConstraint,
                    $package->getName()
                ));
            }

            if ($definition->hasDependency($dependencyName) || $definition->hasDevDependency($dependencyName)) {
                $definition->save();
            }
        }

        return 0;
    }
}
