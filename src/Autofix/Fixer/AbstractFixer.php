<?php

declare(strict_types=1);

namespace Nusje2000\ComposerMonolith\Autofix\Fixer;

use Nusje2000\ComposerMonolith\Autofix\FixerInterface;
use Symfony\Component\Console\Style\OutputStyle;

abstract class AbstractFixer implements FixerInterface
{
    /**
     * @var OutputStyle
     */
    protected $output;

    public function __construct(OutputStyle $output)
    {
        $this->output = $output;
    }

    public function error(string $error): void
    {
        $this->output->writeln(sprintf('<error>[ERROR]</error> %s', $error));
    }

    public function solution(string $solution): void
    {
        $this->output->writeln(sprintf('<success>[SOLUTION]</success> %s', $solution));
    }
}
