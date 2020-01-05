<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Composer\Semver\VersionParser;
use Nusje2000\ComposerMonolith\Validator\Violation\MissingDependencyViolation;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\Composer\PackageDefinition;
use Nusje2000\DependencyGraph\DependencyGraph;
use Symfony\Component\Console\Style\OutputStyle;

final class MissingDependencyFixer extends AbstractFixer
{
    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct(OutputStyle $output)
    {
        parent::__construct($output);
        $this->versionParser = new VersionParser();
    }

    public function fix(DependencyGraph $graph, ViolationCollection $violations): void
    {
        /** @var array<string, string[]> $missingDependencies */
        $missingDependencies = [];

        /** @var array<string, array<ViolationInterface>> $violationFixes */
        $violationFixes = [];

        /** @var array<string, bool> $devDependencies */
        $devDependencies = [];

        foreach ($violations as $violation) {
            if ($violation instanceof MissingDependencyViolation) {
                $dependency = $violation->getDependency();
                $missingDependencies[$dependency->getName()][] = $dependency->getVersionConstraint();

                $violationFixes[$dependency->getName()][] = $violation;

                if ($dependency->isDev() && false !== $devDependencies[$dependency->getName()] ?? true) {
                    $devDependencies[$dependency->getName()] = true;
                } else {
                    $devDependencies[$dependency->getName()] = false;
                }
            }
        }

        if (empty($missingDependencies)) {
            return;
        }

        $rootDefinition = PackageDefinition::createFromDirectory($graph->getRootPath());
        foreach ($missingDependencies as $name => $versionConstraints) {
            $isDevDependency = $devDependencies[$name];
            $versionConstraint = $this->guessVersion($versionConstraints);

            if (null === $versionConstraint) {
                $versionConstraint = $this->output->ask(
                    sprintf('What version of "%s" would you like to require (referenced as [%s]) ?', $name, implode(', ', $versionConstraints))
                );
            }

            if (null === $versionConstraint) {
                $this->output->writeln(sprintf('[ERROR] Could not resolve version constraint for dependency on "%s".', $versionConstraint));

                continue;
            }

            if ($isDevDependency) {
                $this->output->writeln(sprintf('[SOLUTION] added pacakge "%s" to the dev-dependencies (version: %s)', $name, $versionConstraint));
                $rootDefinition->addDevDependency($name, $versionConstraint);
            } else {
                $this->output->writeln(sprintf('[SOLUTION] added pacakge "%s" to the dev-dependencies (version: %s)', $name, $versionConstraint));
                $rootDefinition->setDependency($name, $versionConstraint);
            }

            foreach ($violationFixes[$name] ?? [] as $violation) {
                $violations->remove($violation);
            }
        }

        $rootDefinition->save();
    }

    /**
     * @param array<string> $versionConstraints
     */
    private function guessVersion(array $versionConstraints): ?string
    {
        foreach ($versionConstraints as $baseVersionString) {
            $baseVersionConstraint = $this->versionParser->parseConstraints($baseVersionString);

            $incompatibleVersions = array_filter(
                $versionConstraints,
                function (string $compareVersionString) use ($baseVersionConstraint): bool {
                    $compareVersionConstraint = $this->versionParser->parseConstraints($compareVersionString);

                    return !$baseVersionConstraint->matches($compareVersionConstraint);
                }
            );

            if (empty($incompatibleVersions)) {
                return $baseVersionString;
            }
        }

        return null;
    }
}
