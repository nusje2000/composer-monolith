<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Console\Input\InputOption;

final class EqualizeVersionCommand extends AbstractDependencyGraphCommand
{
    protected static $defaultName = 'version-equalize';

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription(
            'Check for packages that have differences in their version and be able to "equalize" these.'
        );

        $this->addOption('autofix', 'f', InputOption::VALUE_NONE, 'Try to fix the validations automatically.');
    }

    protected function doExecute(DependencyGraph $graph): int
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
            $this->io->warning('No dependencies found that could be equalized.');

            return 1;
        }

        foreach ($dependencies as $name => $versions) {
            $this->io->writeln(sprintf('Equalizable dependency on <dependency>%s</dependency> (references: %s)', $name, $this->formatReferences($versions)));
        }

        $selectedDependency = null;
        while (null === $selectedDependency || !isset($dependencies[$selectedDependency])) {
            $selectedDependency = $this->io->ask('Which dependency would you like to equalize ?');

            if (!is_string($selectedDependency)) {
                $this->io->warning('Aborting due to no dependency selected...');

                return 1;
            }

            if (!isset($dependencies[$selectedDependency])) {
                $this->io->warning(sprintf('Dependency "%s" was not found in the list of equalizable dependencies.', $selectedDependency));
            }
        }

        $selectedVersion = $this->io->ask(sprintf(
            'What would you like the new version constraint to be for the dependency on "%s" ?',
            $selectedDependency
        ));

        if (!is_string($selectedVersion)) {
            $this->io->warning('Aborting due to no version selected...');

            return 1;
        }

        $shouldUpdate = $this->io->confirm(sprintf(
            'Are you sure you would like to set the version constraint on "%s" to "%s" in %d packages ?',
            $selectedDependency,
            $selectedVersion,
            count($dependencies[$selectedDependency] ?? [])
        ));

        if (!$shouldUpdate) {
            $this->io->warning('Aborting...');

            return 1;
        }

        foreach ($packages as $package) {
            if (!$package->hasDependency($selectedDependency)) {
                continue;
            }

            $dependency = $package->getDependency($selectedDependency);

            $definition = PackageDefinition::createFromDirectory($package->getPackageLocation());

            if ($definition->hasDependency($selectedDependency)) {
                $definition->setDependency($selectedDependency, $selectedVersion);

                $this->io->writeln(sprintf(
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

                $this->io->writeln(sprintf(
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

        return 0;
    }

    /**
     * @param array<string, string> $versions
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

        return implode(', ', array_merge(
            [sprintf('<package>default</package>: <version>%s</version>', $default)],
            $references
        ));
    }
}
