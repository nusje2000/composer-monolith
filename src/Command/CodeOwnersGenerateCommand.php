<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\CodeOwners\Builder;
use Nusje2000\DependencyGraph\DependencyGraph;

final class CodeOwnersGenerateCommand extends AbstractDependencyGraphCommand
{
    protected static $defaultName = 'codeowners:generate';

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Update the root .CODEOWNERS file.');
    }

    protected function doExecute(DependencyGraph $graph): int
    {
        $file = Builder::fromGraph($graph);
        $saveLocation = $graph->getRootPath() . DIRECTORY_SEPARATOR . 'CODEOWNERS';

        $success = file_put_contents($saveLocation, $file->toString());

        if (false === $success) {
            $this->io->error(sprintf('Could not write contents to %s.', $saveLocation));

            return 1;
        }

        $this->io->success(sprintf('Saved CODEOWNERS file to "%s"', $saveLocation));

        return 0;
    }
}
