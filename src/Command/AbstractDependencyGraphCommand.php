<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Command;

use Nusje2000\ComposerMonolith\Formatter\OutputFormatter;
use Nusje2000\DependencyGraph\DependencyGraph;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractDependencyGraphCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    protected function configure(): void
    {
        $this->addOption('root', 'r', InputOption::VALUE_REQUIRED, 'Set the root path relative to the current working directory.');
    }

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectRoot = getcwd();

        $this->input = $input;
        $this->output = $output;

        $this->io = new SymfonyStyle($input, $output);
        $this->io->setFormatter(new OutputFormatter());

        $overrideRoot = $input->getOption('root');
        if (is_string($overrideRoot)) {
            $projectRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . $overrideRoot);
        }

        if (!is_string($projectRoot)) {
            $this->io->error(sprintf('"%s" is not a valid path.', $projectRoot));

            return 1;
        }

        $graph = DependencyGraph::build($projectRoot);

        return $this->doExecute($graph);
    }

    abstract protected function doExecute(DependencyGraph $graph): int;
}
