<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Formatter\OutputFormatter;
use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UpdateCommand extends Command
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectRoot = getcwd();

        $io = new SymfonyStyle($input, $output);
        $io->setFormatter(new OutputFormatter());

        $overrideRoot = $input->getOption('root');
        if (is_string($overrideRoot)) {
            $projectRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . $overrideRoot);
        }

        if (!is_string($projectRoot)) {
            $io->error(sprintf('"%s" is not a valid path.', $projectRoot));

            return 1;
        }

        $graph = DependencyGraph::build($projectRoot);

        $dependencyName = $input->getArgument('dependency');
        $versionConstraint = $input->getArgument('version_constraint');

        $packages = $graph->getPackages()->filter(static function (PackageInterface $package): bool {
            return !$package->isFromVendor();
        });

        foreach ($packages as $package) {
            $definition = PackageDefinition::createFromDirectory($package->getPackageLocation());

            if ($definition->hasDependency($dependencyName)) {
                $currentVersion = $definition->getDependencyVersionConstraint($dependencyName);
                $definition->setDependency($dependencyName, $versionConstraint);

                $io->writeln(sprintf(
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

                $io->writeln(sprintf(
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
