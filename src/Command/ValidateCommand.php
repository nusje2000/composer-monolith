<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Autofix\Fixer;
use Nusje2000\ComposerMonolith\Autofix\FixerCollection;
use Nusje2000\ComposerMonolith\Autofix\ViolationFixer;
use Nusje2000\ComposerMonolith\Validator\Rule;
use Nusje2000\ComposerMonolith\Validator\RuleCollection;
use Nusje2000\ComposerMonolith\Validator\Validator;
use Nusje2000\DependencyGraph\DependencyGraph;
use Symfony\Component\Console\Input\InputOption;

final class ValidateCommand extends AbstractDependencyGraphCommand
{
    protected static $defaultName = 'validate';

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription(
            'Validate definition files within the project. This is supposed to be used within monolithic repositories to make sure' .
            'that all dependencies defined by sub-packages are present in the root package definition.'
        );

        $this->addOption('autofix', 'f', InputOption::VALUE_NONE, 'Try to fix the validations automatically.');
    }

    protected function doExecute(DependencyGraph $graph): int
    {
        $validator = new Validator(new RuleCollection([
            new Rule\MissingDependencyRule(),
            new Rule\IncompatibleVersionRule(),
            new Rule\MissingReplaceRule(),
        ]));

        $fixer = new ViolationFixer(new FixerCollection([
            new Fixer\MissingDependencyFixer($this->io),
            new Fixer\IncompatibleVersionFixer($this->io),
            new Fixer\MissingReplaceFixer($this->io),
        ]));

        $violations = $validator->getViolations($graph);

        if ($violations->isEmpty()) {
            $this->io->success(sprintf('Project located at "%s" is valid.', $graph->getRootPath()));

            return 0;
        }

        foreach ($violations as $violation) {
            $this->io->writeln(sprintf('<violation>[VIOLATION]</violation> %s', $violation->getFormattedMessage()));
        }

        if (!empty($violations)) {
            $this->io->newLine();
            $this->io->error(sprintf('Analysis complete, total of %d violations found.', $violations->count()));
            $this->io->newLine();
        }

        if ($this->input->getOption('autofix')) {
            $leftViolations = $fixer->fix($graph, $violations);

            $this->io->section('Autofix report');
            $this->io->writeln(sprintf('Violations before autofixing: %s', $violations->count()));
            $this->io->writeln(sprintf('Violations after autofixing:  %s', $leftViolations->count()));
            $this->io->writeln(sprintf('Total fixed violations:       %s', $violations->count() - $leftViolations->count()));

            if (!$leftViolations->isEmpty()) {
                $this->io->section('Remaining violations');

                foreach ($leftViolations as $violation) {
                    $this->io->writeln(sprintf('<violation>[VIOLATION]</violation> %s', $violation->getFormattedMessage()));
                }

                return 1;
            }

            return 0;
        }

        $this->io->writeln('Try running the validator with the --autofix option to automatically fix most of the violations.');

        return 1;
    }
}
