<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\CodeOwners\Builder;
use Nusje2000\DependencyGraph\DependencyGraph;

final class CodeOwnersValidateCommand extends AbstractDependencyGraphCommand
{
    protected static $defaultName = 'codeowners:validate';

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Validate the root CODEOWNERS file.');
    }

    protected function doExecute(DependencyGraph $graph): int
    {
        $fileName = $graph->getRootPath() . DIRECTORY_SEPARATOR . 'CODEOWNERS';
        if (!file_exists($fileName)) {
            $this->io->error(sprintf('Could not find file "%s".', $fileName));

            return 1;
        }

        $contents = file_get_contents($fileName);
        if (false === $contents) {
            $this->io->error(sprintf('Could get contents of file "%s".', $fileName));

            return 1;
        }

        $expectedFile = Builder::fromGraph($graph);

        $errors = [];
        foreach ($expectedFile->getRules() as $rule) {
            if (false === strpos($contents, $rule->toString())) {
                $errors[] = sprintf('File is missing rule "<rule>%s</rule>"', $rule->toString());
            }
        }

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->io->writeln(sprintf('<error>[ERROR]</error> %s', $error));
            }

            $this->io->error(sprintf(
                'Invalid CODEOWNERS file, %d missing lines found. Use codeowners:update to generate' .
                'a valid codeowners file or add the missing rules to the CODEOWNERS file.',
                count($errors)
            ));

            return 1;
        }

        $this->io->success('CODEOWNERS file is valid.');

        return 0;
    }
}
