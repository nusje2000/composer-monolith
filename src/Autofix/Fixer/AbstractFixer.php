<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Autofix\FixerInterface;
use Nusje2000\ComposerMonolith\Autofix\VersionGuesser;
use Nusje2000\DependencyGraph\DependencyGraph;
use Nusje2000\DependencyGraph\Package\PackageInterface;
use Symfony\Component\Console\Style\OutputStyle;

abstract class AbstractFixer implements FixerInterface
{
    /**
     * @var OutputStyle
     */
    protected $output;

    /**
     * @var VersionGuesser
     */
    protected $versionGuesser;

    public function __construct(OutputStyle $output)
    {
        $this->output = $output;
        $this->versionGuesser = new VersionGuesser();
    }

    public function error(string $error): void
    {
        $this->output->writeln(sprintf('<error>[ERROR]</error> %s', $error));
    }

    public function solution(string $solution): void
    {
        $this->output->writeln(sprintf('<success>[SOLUTION]</success> %s', $solution));
    }

    protected function resolveRequiredVersion(DependencyGraph $graph, string $dependencyName): ?string
    {
        $referencedVersions = $this->getReferencedVersions($graph, $dependencyName);
        $versionConstraint = $this->versionGuesser->guess($referencedVersions);

        if (null === $versionConstraint) {
            $references = array_map(static function (string $version, string $package): string {
                return sprintf('%s,: %s', $package, $version);
            }, $referencedVersions, array_keys($referencedVersions));

            $versionConstraint = $this->output->ask(sprintf(
                'What version of "%s" would you like to require (referenced as [%s]) ?',
                $dependencyName,
                implode(', ', $references)
            ));
        }

        return $versionConstraint;
    }

    /**
     * @return array<string, string>
     */
    protected function getReferencedVersions(DependencyGraph $graph, string $dependencyName): array
    {
        /** @var array<string, string> $referncedVersions */
        $referncedVersions = $graph->getSubPackages()->filter(static function (PackageInterface $package) use ($dependencyName): bool {
            return $package->hasDependency($dependencyName);
        })->map(static function (PackageInterface $package) use ($dependencyName): string {
            return $package->getDependency($dependencyName)->getVersionConstraint();
        });

        return $referncedVersions;
    }
}
