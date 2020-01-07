<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Formatter\OutputFormatter;
use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class EqualizeVersionCommand extends Command
{
    protected static $defaultName = 'version-equalize';

    protected function configure(): void
    {
        $this->setDescription(
            'Check for packages that have differences in their version and be able to "equalize" these.'
        );

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
        $this->equalize($graph, $io);

        return 0;
    }

    private function equalize(DependencyGraph $graph, SymfonyStyle $io): void
    {
        $packages = $graph->getPackages()->filter(static function (PackageInterface $package): bool {
            return !$package->isFromVendor();
        });

        /** @var array<string, array<string, string>> $dependencies */
        $dependencies = [];
        foreach ($packages as $package) {
            foreach ($package->getDependencies() as $dependency) {
                $dependencies[$dependency->getName()][$package->getName()] = $dependency->getVersionConstraint();
            }
        }

        $dependencies = array_filter($dependencies, static function (array $versions): bool {
            return count(array_unique($versions)) > 1;
        });

        if (empty($dependencies)) {
            $io->warning('No dependencies found that could be equalized.');

            return;
        }

        foreach ($dependencies as $name => $versions) {
            $io->writeln(sprintf('Equalizable dependency on <dependency>%s</dependency> (references: %s)', $name, $this->formatReferences($versions)));
        }

        $selectedDependency = null;
        while (null === $selectedDependency || !isset($dependencies[$selectedDependency])) {
            $selectedDependency = $io->ask('Which dependency would you like to equalize ?');

            if (!is_string($selectedDependency)) {
                $io->warning('Aborting due to no dependency selected...');

                return;
            }

            if (!isset($dependencies[$selectedDependency])) {
                $io->warning(sprintf('Dependency "%s" was not found in the list of equalizable dependencies.', $selectedDependency));
            }
        }

        $selectedVersion = $io->ask(sprintf(
            'What would you like the new version constraint to be for the dependency on "%s" ?',
            $selectedDependency
        ));

        if (!is_string($selectedVersion)) {
            $io->warning('Aborting due to no version selected...');

            return;
        }

        $shouldUpdate = $io->confirm(sprintf(
            'Are you sure you would like to set the version constraint on "%s" to "%s" in %d packages ?',
            $selectedDependency,
            $selectedVersion,
            count($dependencies[$selectedDependency] ?? [])
        ));

        if (!$shouldUpdate) {
            $io->warning('Aborting...');

            return;
        }

        foreach ($packages as $package) {
            if (!$package->hasDependency($selectedDependency)) {
                continue;
            }

            $dependency = $package->getDependency($selectedDependency);

            $definition = PackageDefinition::createFromDirectory($package->getPackageLocation());

            if ($definition->hasDependency($selectedDependency)) {
                $definition->setDependency($selectedDependency, $selectedVersion);

                $io->writeln(sprintf(
                    '<success>[SUCCESS]</success> changed dependency on <dependency>"%s"</dependency> from ' .
                    'version <version>%s</version> to <version>%s</version> in package <package>"%s"</package>',
                    $dependency->getName(),
                    $dependency->getVersionConstraint(),
                    $selectedVersion,
                    $package->getName()
                ));
            }

            if ($definition->hasDevDependency($selectedDependency)) {
                $definition->setDevDependency($selectedDependency, $selectedVersion);

                $io->writeln(sprintf(
                    '<success>[SUCCESS]</success> changed dev-dependency on <dependency>"%s"</dependency> from ' .
                    'version <version>%s</version> to <version>%s</version> in package <package>"%s"</package>',
                    $dependency->getName(),
                    $dependency->getVersionConstraint(),
                    $selectedVersion,
                    $package->getName()
                ));
            }

            $definition->save();
        }
    }

    /**
     * @param array<stirng, string> $versions
     */
    private function formatReferences(array $versions): string
    {
        $counts = array_count_values($versions);
        arsort($counts);
        $default = array_key_first($counts);

        $references = array_filter($versions, static function (string $version) use ($default) {
            return $version !== $default;
        });

        $references = array_map(static function (string $version, string $package) {
            return sprintf('<package>%s</package>: <version>%s</version>', $package, $version);
        }, $references, array_keys($references));

        return implode(array_merge(
            [sprintf('<package>default</package>: <version>%s</version>', $default)],
            $references
        ), ', ');
    }
}
