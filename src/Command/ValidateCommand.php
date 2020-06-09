<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Autofix\Fixer;
use Nusje2000\ComposerMonolith\Autofix\FixerCollection;
use Nusje2000\ComposerMonolith\Autofix\ViolationFixer;
use Nusje2000\ComposerMonolith\Validator\Rule;
use Nusje2000\ComposerMonolith\Validator\RuleCollection;
use Nusje2000\ComposerMonolith\Validator\Validator;
use Nusje2000\ComposerMonolith\Validator\ViolationCollection;
use Nusje2000\ComposerMonolith\Validator\ViolationInterface;
use Nusje2000\DependencyGraph\DependencyGraph;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

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
        $this->addOption('static-analysis', null, InputOption::VALUE_NONE, 'Use static analysis to analyze referenced classes in code.');
        $this->addOption(
            'generate-baseline',
            null,
            InputOption::VALUE_NONE,
            'When generating a baseline, all the current errors will be placed into a file and those errors will be ignored in future executions.'
        );
    }

    protected function doExecute(DependencyGraph $graph): int
    {
        $validator = new Validator($this->getRules($this->input));

        $fixer = new ViolationFixer(new FixerCollection([
            new Fixer\MissingDependencyFixer($this->io),
            new Fixer\IncompatibleVersionFixer($this->io),
            new Fixer\MissingReplaceFixer($this->io),
        ]));

        $violations = $validator->getViolations($graph);
        $filteredViolations = $this->filterViolations($graph, $violations);

        if ($filteredViolations->isEmpty()) {
            $this->io->success(sprintf('Project located at "%s" is valid.', $graph->getRootPath()));

            return 0;
        }

        foreach ($filteredViolations as $violation) {
            $this->io->writeln(sprintf('<violation>[VIOLATION]</violation> %s', $violation->getFormattedMessage()));
        }

        if (!empty($filteredViolations)) {
            $this->io->newLine();
            $this->io->error(sprintf('Analysis complete, total of %d violations found.', $filteredViolations->count()));
            $this->io->newLine();
        }

        if ($this->input->getOption('autofix')) {
            $leftViolations = $fixer->fix($graph, $filteredViolations);

            $this->io->section('Autofix report');
            $this->io->writeln(sprintf('Violations before autofixing: %s', $filteredViolations->count()));
            $this->io->writeln(sprintf('Violations after autofixing:  %s', $leftViolations->count()));
            $this->io->writeln(sprintf('Total fixed violations:       %s', $filteredViolations->count() - $leftViolations->count()));

            if (!$leftViolations->isEmpty()) {
                $this->io->section('Remaining violations');

                foreach ($leftViolations as $violation) {
                    $this->io->writeln(sprintf('<violation>[VIOLATION]</violation> %s', $violation->getFormattedMessage()));
                }

                return 1;
            }

            return 0;
        }

        if ($this->input->getOption('generate-baseline')) {
            $this->writeBaseline($graph, $violations);
            $this->io->success(sprintf('Generated baseline containing %d ignored violations.', $violations->count()));
        }

        $this->io->writeln('Try running the validator with the --autofix option to automatically fix most of the violations.');

        return 1;
    }

    private function filterViolations(DependencyGraph $graph, ViolationCollection $violations): ViolationCollection
    {
        if (!file_exists($this->getBaselinePath($graph))) {
            return $violations;
        }

        $contents = file_get_contents($this->getBaselinePath($graph));
        if (false === $contents) {
            throw new UnexpectedValueException('Could not read baseline.');
        }

        $ignored = Yaml::parse(file_get_contents($this->getBaselinePath($graph)) ?? '')['ignore'] ?? [];
        if (!is_array($ignored)) {
            throw new UnexpectedValueException('Expected ignored files to be array.');
        }

        return $violations->filter(static function (ViolationInterface $violation) use ($ignored): bool {
            return !in_array($violation->getMessage(), $ignored, true);
        });
    }

    private function writeBaseline(DependencyGraph $graph, ViolationCollection $violations): void
    {
        file_put_contents(
            $this->getBaselinePath($graph),
            Yaml::dump(['ignore' => $violations->getMessages()->toArray()])
        );
    }

    private function getRules(InputInterface $input): RuleCollection
    {
        $rules = new RuleCollection([
            new Rule\MissingDependencyRule(),
            new Rule\IncompatibleVersionRule(),
            new Rule\MissingReplaceRule(),
        ]);

        if ($input->getOption('static-analysis')) {
            $rules->append(new Rule\OutOfBoundsClassReferenceRule($this->logger));
        }

        return $rules;
    }

    private function getBaselinePath(DependencyGraph $graph): string
    {
        return $graph->getRootPath() . DIRECTORY_SEPARATOR . 'composer-monolith-baseline.yaml';
    }
}
