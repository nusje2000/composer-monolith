<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Autofix\Fixer;
use Nusje2000\ComposerMonolith\Autofix\FixerCollection;
use Nusje2000\ComposerMonolith\Autofix\ViolationFixer;
use Nusje2000\ComposerMonolith\Formatter\OutputFormatter;
use Nusje2000\ComposerMonolith\Validator\Rule;
use Nusje2000\ComposerMonolith\Validator\RuleCollection;
use Nusje2000\ComposerMonolith\Validator\Validator;
use Nusje2000\DependencyGraph\DependencyGraph;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ValidateCommand extends Command
{
    protected static $defaultName = 'validate';

    protected function configure(): void
    {
        $this->setDescription(
            'Validate definition files within the project. This is supposed to be used within monolithic repositories to make sure' .
            'that all dependencies defined by sub-packages are present in the root package definition.'
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

        $validator = new Validator(new RuleCollection([
            new Rule\MissingDependencyRule(),
            new Rule\IncompatibleVersionRule(),
        ]));

        $fixer = new ViolationFixer(new FixerCollection([
            new Fixer\MissingDependencyFixer($io),
            new Fixer\IncompatibleVersionFixer($io),
        ]));

        $violations = $validator->validate($graph);

        if ($violations->isEmpty()) {
            $io->success(sprintf('Project located at "%s" is valid.', $projectRoot));

            return 0;
        }

        foreach ($violations as $violation) {
            $io->writeln(sprintf('<violation>[VIOLATION]</violation> %s', $violation->getFormattedMessage()));
        }

        if (!empty($violations)) {
            $io->newLine();
            $io->error(sprintf('Analysis complete, total of %d violations found.', $violations->count()));
            $io->newLine();
        }

        if ($input->getOption('autofix')) {
            $leftViolations = $fixer->fix($graph, $violations);

            $io->section('Autofix report');
            $io->writeln(sprintf('Violations before autofixing: %s', $violations->count()));
            $io->writeln(sprintf('Violations after autofixing:  %s', $leftViolations->count()));
            $io->writeln(sprintf('Total fixed violations:       %s', $violations->count() - $leftViolations->count()));

            if (!$leftViolations->isEmpty()) {
                $io->section('Remaining violations');

                foreach ($leftViolations as $violation) {
                    $io->writeln(sprintf('<violation>[VIOLATION]</violation> %s', $violation->getFormattedMessage()));
                }

                return 1;
            }

            return 0;
        }

        $io->writeln('Try running the validator with the --autofix option to automatically fix most of the violations.');

        return 1;
    }
}
