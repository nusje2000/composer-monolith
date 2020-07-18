<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Composer\DefinitionMutatorFactory;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

final class UpdateCommand extends AbstractDependencyGraphCommand
{
    protected static $defaultName = 'update';

    /**
     * @var DefinitionMutatorFactory
     */
    private $mutatorFactory;

    public function __construct()
    {
        parent::__construct();
        $this->mutatorFactory = new DefinitionMutatorFactory();
    }

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

        $updates = 0;
        foreach ($packages as $package) {
            $mutator = $this->mutatorFactory->createByPackage($package);

            if (!$package->hasDependency($dependencyName)) {
                continue;
            }

            $dependency = $package->getDependency($dependencyName);
            if (!$dependency->isDev()) {
                $mutator->setDependency($dependencyName, $versionConstraint);

                $this->io->writeln(sprintf(
                    '<success>[SUCCESS]</success> changed dependency on <dependency>"%s"</dependency> from ' .
                    'version <version>%s</version> to <version>%s</version> in package <package>"%s"</package>',
                    $dependencyName,
                    $dependency->getVersionConstraint(),
                    $versionConstraint,
                    $package->getName()
                ));
            } else {
                $mutator->setDevDependency($dependencyName, $versionConstraint);

                $this->io->writeln(sprintf(
                    '<success>[SUCCESS]</success> changed dev-dependency on <dependency>"%s"</dependency> from ' .
                    'version <version>%s</version> to <version>%s</version> in package <package>"%s"</package>',
                    $dependencyName,
                    $dependency->getVersionConstraint(),
                    $versionConstraint,
                    $package->getName()
                ));
            }

            $updates++;
            $mutator->save();
        }

        $this->output->writeln(sprintf('<success>[SUCCESS]</success> Updated %d packages.', $updates));

        return 0;
    }
}
