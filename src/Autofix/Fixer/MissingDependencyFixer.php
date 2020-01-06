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

                if ($dependency->isDev() && !isset($devDependencies[$dependency->getName()])) {
                    $devDependencies[$dependency->getName()] = true;
                } elseif (!$dependency->isDev()) {
                    $devDependencies[$dependency->getName()] = false;
                }
            }
        }

        if (empty($missingDependencies)) {
            return;
        }

        $rootDefinition = PackageDefinition::createFromDirectory($graph->getRootPath());
        foreach ($missingDependencies as $name => $versionConstraints) {
            $isDevDependency = $devDependencies[$name] ?? false;
            $versionConstraint = $this->guessVersion($versionConstraints);

            if (null === $versionConstraint) {
                $versionConstraint = $this->output->ask(
                    sprintf('What version of "%s" would you like to require (referenced as [%s]) ?', $name, implode(', ', $versionConstraints))
                );
            }

            if (null === $versionConstraint) {
                $this->error(sprintf('Could not resolve version constraint for dependency on <dependency>"%s"</dependency>.', $name));

                continue;
            }

            if ($isDevDependency) {
                $this->solution(sprintf(
                    'Added pacakge <dependency>"%s"</dependency> to the dev-dependencies (version: <version>%s</version>)',
                    $name,
                    $versionConstraint
                ));
                $rootDefinition->addDevDependency($name, $versionConstraint);
            } else {
                $this->solution(sprintf(
                    'Added pacakge <dependency>"%s"</dependency> to the dev-dependencies (version: <version>%s</version>)',
                    $name,
                    $versionConstraint
                ));
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
